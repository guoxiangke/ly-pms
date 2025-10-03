<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\JoomlaContentService;
use Exception;

class PostContentToJoomla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:rly
                            {--sourceCategory=1453 : Source category ID (default: 1453)}
                            {--destCategory=13 : Destination category ID (default: 13)}
                            {--limit=0 : Limit number of articles to process (0 = no limit)}
                            {--dry-run : Show what would be imported without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from source API to Joomla destination';

    /**
     * Source API configuration
     */
    private $sourceApiUrl = 'https://r.729ly.net/category_articles_api.php';
    
    /**
     * Joomla Content Service
     */
    private $joomlaService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(JoomlaContentService $joomlaService)
    {
        parent::__construct();
        $this->joomlaService = $joomlaService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceCategory = $this->option('sourceCategory');
        $destCategory = $this->option('destCategory');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info("Starting sync from category {$sourceCategory} to category {$destCategory}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual changes will be made");
        }

        try {
            // 1. 从源API获取文章数据
            $this->info("Fetching articles from source API...");
            $articlesData = $this->fetchSourceArticles($sourceCategory);
            
            if (!$articlesData) {
                $this->error("Failed to fetch articles from source API");
                return Command::FAILURE;
            }

            $this->info("Found {$articlesData['total']} articles in category: {$articlesData['category_info']['title']}");
            
            // 2. 处理每篇文章
            $articles = $articlesData['articles'];
            $totalArticles = count($articles);
            
            if ($limit > 0 && $limit < $totalArticles) {
                $articles = array_slice($articles, 0, $limit);
                $this->info("Limited to processing {$limit} articles");
            }

            $this->info("Processing {$totalArticles} articles...");
            
            $successCount = 0;
            $errorCount = 0;
            
            // 使用进度条
            $progressBar = $this->output->createProgressBar(count($articles));
            $progressBar->start();

            foreach ($articles as $article) {
                try {
                    $result = $this->processArticle($article, $destCategory, $dryRun);
                    
                    if ($result['success']) {
                        $successCount++;
                        if ($dryRun) {
                            $this->line("\n✓ Would process: {$article['title']}");
                            $this->line("  Source alias: {$result['source_alias']}");
                            $this->line("  Dest alias: {$result['dest_alias']}");
                        } else {
                            $this->line("\n✓ {$result['action']}: {$article['title']} (ID: {$result['id']})");
                            $this->line("  Alias: {$result['source_alias']} → {$result['dest_alias']}");
                        }
                    } else {
                        $errorCount++;
                        $this->line("\n✗ Failed: {$article['title']} - {$result['error']}");
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->line("\n✗ Error processing {$article['title']}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            
            // 3. 显示结果统计
            $this->newLine(2);
            $this->info("Import completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Success', $successCount],
                    ['Errors', $errorCount],
                    ['Total', count($articles)]
                ]
            );

            return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 从源API获取文章数据
     *
     * @param int $categoryId
     * @return array|null
     */
    private function fetchSourceArticles(int $categoryId): ?array
    {
        try {
            $response = Http::timeout(30)->get($this->sourceApiUrl, [
                'category_id' => $categoryId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success'] ?? false) {
                    return $data;
                } else {
                    $this->error("Source API returned error: " . ($data['message'] ?? 'Unknown error'));
                    return null;
                }
            } else {
                $this->error("HTTP error {$response->status()} when fetching from source API");
                return null;
            }
            
        } catch (Exception $e) {
            $this->error("Exception when fetching from source API: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 清理文章内容
     *
     * @param string $content
     * @return string
     */
    private function cleanContent(string $content): string
    {
        // 1. 移除包含 hide_attachments_token 的 span 标签及其内容
        $content = preg_replace('/<span[^>]*class="hide_attachments_token"[^>]*>.*?<\/span>/', '', $content);
        
        // 2. 移除 {sermonspeaker 数字,数字} 格式的内容
        $content = preg_replace('/\{sermonspeaker\s+\d+,\d+\}/', '', $content);
        
        // 3. 移除 {attachments} 标签
        $content = preg_replace('/\{attachments\}/', '', $content);
        
        // 4. 移除空的 <p> 标签
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        // 5. 清理多余的换行符和空白字符
        // $content = preg_replace('/\r\n\s*\r\n/', "\r\n", $content);
        // $content = trim($content);
        
        return $content;
    }

    /**
     * 处理单篇文章
     *
     * @param array $article
     * @param int $destCategory
     * @param bool $dryRun
     * @return array
     */
    private function processArticle(array $article, int $destCategory, bool $dryRun): array
    {
        try {
            $title = $article['title'] ?? '';
            $originalContent = $article['content'] ?? '';
            $sourceAlias = $article['alias'] ?? '';

            if (empty($title) || empty($sourceAlias)) {
                return [
                    'success' => false,
                    'error' => 'Missing title or alias'
                ];
            }

            // 清理内容
            $content = $this->cleanContent($originalContent);

            // 处理alias: 提取最后6位字符
            // 例如：sermon-gw-gw250809 → gw250809
            //      sermon-gaw-gaw250809 → gaw250809
            $parts = explode('-', $sourceAlias); 
            $destAlias = end($parts);
            
            if (empty($destAlias)) {
                return [
                    'success' => false,
                    'error' => "Cannot extract destination alias from: {$sourceAlias}"
                ];
            }

            // 如果是dry run模式，只显示信息不实际操作
            if ($dryRun) {
                return [
                    'success' => true,
                    'action' => 'would_process',
                    'id' => 'dry_run',
                    'title' => $title,
                    'source_alias' => $sourceAlias,
                    'dest_alias' => $destAlias
                ];
            }

            // 准备选项
            $options = [
                'state' => 1, // 发布状态
                'featured' => 0,
                'access' => 1, // 公开访问
                'language' => '*' // 所有语言
            ];

            // 使用JoomlaContentService创建或更新文章
            $result = $this->joomlaService->createOrUpdateArticle(
                $title,
                $content, // 使用清理后的内容
                $destAlias, // 使用提取的最后6位字符
                $options
            );

            return [
                'success' => true,
                'action' => $result['action'],
                'id' => $result['id'],
                'url' => $result['url'] ?? '',
                'source_alias' => $sourceAlias,
                'dest_alias' => $destAlias
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}