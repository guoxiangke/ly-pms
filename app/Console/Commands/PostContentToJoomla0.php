<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\JoomlaContentService;
use Exception;
use Illuminate\Support\Facades\Log;

class PostContentToJoomla0 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // 规则的，可以从// 处理alias: 提取最后6位字符
            // 例如：sermon-gw-gw250809 → gw250809
    protected $signature = 'sync:rly0
                            {--sourceCategory=22 : Source category ID(s) - comma separated (e.g., 722,723,724)}
                            {--destCategory=21 : Destination category ID (default: 13)}
                            {--limit=0 : Limit number of articles to process per category (0 = no limit)}
                            {--dry-run : Show what would be imported without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from source API to Joomla destination (supports multiple source categories)';

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
        $sourceCategoryParam = $this->option('sourceCategory');
        $destCategory = $this->option('destCategory');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        // 解析源分类ID列表
        $sourceCategories = $this->parseSourceCategories($sourceCategoryParam);
        
        if (empty($sourceCategories)) {
            $this->error("Invalid source category parameter: {$sourceCategoryParam}");
            return Command::FAILURE;
        }

        $this->info("Starting sync from categories [" . implode(', ', $sourceCategories) . "] to category {$destCategory}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual changes will be made");
        }

        try {
            $totalSuccessCount = 0;
            $totalErrorCount = 0;
            $totalArticlesProcessed = 0;
            $categoryResults = [];

            // 处理每个源分类
            foreach ($sourceCategories as $sourceCategory) {
                $this->newLine();
                $this->info("Processing category: {$sourceCategory}");
                $this->line(str_repeat('=', 50));

                $categoryResult = $this->processCategorySync($sourceCategory, $destCategory, $limit, $dryRun);
                
                $categoryResults[] = $categoryResult;
                $totalSuccessCount += $categoryResult['success_count'];
                $totalErrorCount += $categoryResult['error_count'];
                $totalArticlesProcessed += $categoryResult['articles_processed'];

                // 显示每个分类的结果
                $this->displayCategoryResult($sourceCategory, $categoryResult);
            }

            // 显示总体结果统计
            $this->displayOverallResults($categoryResults, $totalSuccessCount, $totalErrorCount, $totalArticlesProcessed);

            return $totalErrorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 解析源分类参数
     *
     * @param string $sourceCategoryParam
     * @return array
     */
    private function parseSourceCategories(string $sourceCategoryParam): array
    {
        $categories = [];
        $parts = explode(',', $sourceCategoryParam);
        
        foreach ($parts as $part) {
            $categoryId = (int) trim($part);
            if ($categoryId > 0) {
                $categories[] = $categoryId;
            }
        }
        
        return array_unique($categories);
    }

    /**
     * 处理单个分类的同步
     *
     * @param int $sourceCategory
     * @param int $destCategory
     * @param int $limit
     * @param bool $dryRun
     * @return array
     */
    private function processCategorySync(int $sourceCategory, int $destCategory, int $limit, bool $dryRun): array
    {
        try {
            // 1. 从源API获取文章数据
            $this->info("Fetching articles from source API...");
            $articlesData = $this->fetchSourceArticles($sourceCategory);
            
            if (!$articlesData) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch articles from source API',
                    'success_count' => 0,
                    'error_count' => 0,
                    'articles_processed' => 0,
                    'category_info' => null
                ];
            }

            $categoryInfo = $articlesData['category_info'] ?? ['title' => 'Unknown'];
            $this->info("Found {$articlesData['total']} articles in category: {$categoryInfo['title']}");
            
            // 2. 处理每篇文章
            $articles = $articlesData['articles'];
            $totalArticles = count($articles);
            
            if ($limit > 0 && $limit < $totalArticles) {
                $articles = array_slice($articles, 0, $limit);
                $this->info("Limited to processing {$limit} articles");
            }

            if (empty($articles)) {
                return [
                    'success' => true,
                    'success_count' => 0,
                    'error_count' => 0,
                    'articles_processed' => 0,
                    'category_info' => $categoryInfo
                ];
            }

            $this->info("Processing " . count($articles) . " articles...");
            
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
                            $this->line("  Publish date: {$result['publish_date']}");
                        } else {
                            $this->line("\n✓ {$result['action']}: {$article['title']} (ID: {$result['id']})");
                            $this->line("  Alias: {$result['source_alias']} → {$result['dest_alias']}");
                            if (isset($result['publish_date'])) {
                                $this->line("  Publish date: {$result['publish_date']}");
                            }
                        }
                    } else {
                        $errorCount++;
                        $this->line("\n✗ Failed: {$article['title']} - {$result['error']}");
                        Log::error(__CLASS__ . " - Category {$sourceCategory}", $article);
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->line("\n✗ Error processing {$article['title']}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            
            return [
                'success' => true,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'articles_processed' => count($articles),
                'category_info' => $categoryInfo
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'success_count' => 0,
                'error_count' => 0,
                'articles_processed' => 0,
                'category_info' => null
            ];
        }
    }

    /**
     * 显示单个分类的处理结果
     *
     * @param int $categoryId
     * @param array $result
     */
    private function displayCategoryResult(int $categoryId, array $result): void
    {
        $categoryTitle = $result['category_info']['title'] ?? 'Unknown';
        
        $this->newLine();
        $this->info("Category {$categoryId} ({$categoryTitle}) Results:");
        
        if ($result['success']) {
            $this->table(
                ['Status', 'Count'],
                [
                    ['Success', $result['success_count']],
                    ['Errors', $result['error_count']],
                    ['Total Processed', $result['articles_processed']]
                ]
            );
        } else {
            $this->error("Category {$categoryId} failed: " . $result['error']);
        }
    }

    /**
     * 显示总体结果
     *
     * @param array $categoryResults
     * @param int $totalSuccessCount
     * @param int $totalErrorCount
     * @param int $totalArticlesProcessed
     */
    private function displayOverallResults(array $categoryResults, int $totalSuccessCount, int $totalErrorCount, int $totalArticlesProcessed): void
    {
        $this->newLine(2);
        $this->info("OVERALL SYNC RESULTS");
        $this->line(str_repeat('=', 60));
        
        // 按分类显示汇总
        $summaryData = [];
        foreach ($categoryResults as $index => $result) {
            $categoryId = array_keys($categoryResults)[$index] ?? 'Unknown';
            $categoryTitle = $result['category_info']['title'] ?? 'Unknown';
            
            $summaryData[] = [
                "Category {$categoryId}",
                $categoryTitle,
                $result['success_count'],
                $result['error_count'],
                $result['articles_processed']
            ];
        }
        
        // 添加总计行
        $summaryData[] = [
            'TOTAL',
            '',
            $totalSuccessCount,
            $totalErrorCount,
            $totalArticlesProcessed
        ];
        
        $this->table(
            ['Category', 'Name', 'Success', 'Errors', 'Total'],
            $summaryData
        );

        // 显示最终状态
        if ($totalErrorCount > 0) {
            $this->error("Sync completed with {$totalErrorCount} errors out of {$totalArticlesProcessed} articles");
        } else {
            $this->info("Sync completed successfully! Processed {$totalArticlesProcessed} articles");
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
        
        // 4. 移除语言标签 <p>普通话</p> 和 <p>粤语</p>
        $content = preg_replace('/<p>\s*普通话\s*<\/p>/', '', $content);
        $content = preg_replace('/<p>\s*粤语\s*<\/p>/', '', $content);
        
        // 5. 移除空的 <p> 标签
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        // 6. 清理多余的换行符和空白字符
        $content = preg_replace('/\r\n\s*\r\n/', "\r\n", $content);
        $content = trim($content);
        
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
            $content = $this->cleanContent($originalContent);
            $sourceAlias = $article['alias'] ?? '';

            if (empty($title) || empty($sourceAlias)) {
                return [
                    'success' => false,
                    'error' => 'Missing title or alias'
                ];
            }

            // 处理alias: 提取最后6位字符
            // 例如：sermon-gw-gw250809 → gw250809
            //      sermon-gaw-gaw250809 → gaw250809
            // $destAlias = substr($sourceAlias, -6);
            $parts = explode('-', $sourceAlias); 
            $destAlias = end($parts);
            
            if (empty($destAlias)) {
                return [
                    'success' => false,
                    'error' => "Cannot extract destination alias from: {$sourceAlias}"
                ];
            }

            // 从alias中提取日期信息
            // 例如：gw250809 → 最后6位 250809 → 2025-08-09
            $publishDate = $this->extractPublishDateFromAlias($destAlias);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot extract valid date from alias: {$destAlias}"
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
                    'dest_alias' => $destAlias,
                    'publish_date' => $publishDate
                ];
            }

            // 准备选项
            $options = [
                'state' => 1, // 发布状态
                'featured' => 0,
                'access' => 1, // 公开访问
                'language' => '*', // 所有语言
                'publish_up' => $publishDate, // 发布开始时间
            ];

            // 使用JoomlaContentService创建或更新文章
            $result = $this->joomlaService->createOrUpdateArticle(
                $title,
                $content,
                $destAlias, // 使用提取的最后6位字符
                $options,
                $destCategory // 传递目标分类ID
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

    /**
     * 从alias中提取发布日期
     * 例如：gw250809 → 2025-08-09 00:00:00
     *      gaw250719 → 2025-07-19 00:00:00
     *
     * @param string $alias
     * @return string|null
     */
    private function extractPublishDateFromAlias(string $alias): ?string
    {
        // 提取最后6位数字：YYMMDD格式
        if (preg_match('/(\d{6})$/', $alias, $matches)) {
            $dateString = $matches[1];
            
            // 解析日期：YYMMDD
            $year = '20' . substr($dateString, 0, 2); // YY → 20YY
            $month = substr($dateString, 2, 2);       // MM
            $day = substr($dateString, 4, 2);         // DD
            
            // 验证日期是否有效
            if (checkdate((int)$month, (int)$day, (int)$year)) {
                return "{$year}-{$month}-{$day} 00:00:00";
            } else {
                \Log::warning("Invalid date extracted from alias", [
                    'alias' => $alias,
                    'extracted_date' => $dateString,
                    'year' => $year,
                    'month' => $month,
                    'day' => $day
                ]);
            }
        }
        
        return null;
    }
}