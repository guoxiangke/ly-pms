<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\JoomlaContentService;
use Exception;
use Illuminate\Support\Facades\Log;

class PostContentToJoomla1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // 情况4: devotionals-psalm191225 → mpa-psalm-191225
    // 情况5: devotionals-dy191203 → dy-daily-bread-191203
    // 情况6: devotionals-dy-church-calendar-250905 → dy-church-calendar-250905
    
    // 处理情况7: "alias": "exposition-dy-dy-verses-20250912", =》 dy250912
    // 处理情况8: "alias": "exposition-ttb-cttb-0001-guide01-20200330",=》ttb200330
    
    protected $signature = 'sync:rly1
                            {--sourceCategory=22 : Source category ID(s) - comma separated (e.g., 525,526,1452)}
                            {--destCategory=21 : Destination category ID (default: 13)}
                            {--limit=0 : Limit number of articles to process per category (0 = no limit)}
                            {--dry-run : Show what would be imported without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import articles from source API to Joomla destination (supports multiple source categories for special alias formats)';

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
        // 临时提升内存限制以处理大量数据
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        
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
                
                $categoryResults[$sourceCategory] = $categoryResult;
                $totalSuccessCount += $categoryResult['success_count'];
                $totalErrorCount += $categoryResult['error_count'];
                $totalArticlesProcessed += $categoryResult['articles_processed'];

                // 显示每个分类的结果
                $this->displayCategoryResult($sourceCategory, $categoryResult);
            }

            // 显示总体结果统计
            $this->displayOverallResults($categoryResults, $totalSuccessCount, $totalErrorCount, $totalArticlesProcessed);

            // 恢复原始内存限制并清理
            ini_set('memory_limit', $originalMemoryLimit);
            gc_collect_cycles();

            return $totalErrorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            
            // 即使出错也要恢复内存限制
            ini_set('memory_limit', $originalMemoryLimit);
            gc_collect_cycles();
            
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

            foreach ($articles as $index => $article) {
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
                        Log::error(__CLASS__ . " - Category {$sourceCategory}", [
                            'article_id' => $article['id'] ?? 'unknown',
                            'article_title' => $article['title'] ?? 'unknown',
                            'error' => $result['error']
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->line("\n✗ Error processing {$article['title']}: " . $e->getMessage());
                }
                
                // 释放已处理的文章数据
                unset($result, $articles[$index]);
                
                $progressBar->advance();
                
                // 每处理50篇文章强制进行垃圾回收
                if (($index + 1) % 50 === 0) {
                    gc_collect_cycles();
                }
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
        foreach ($categoryResults as $categoryId => $result) {
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

            // 处理特殊alias格式
            $aliasResult = $this->processSpecialAlias($sourceAlias);
            
            if (!$aliasResult['success']) {
                return [
                    'success' => false,
                    'error' => $aliasResult['error']
                ];
            }
            
            $destAlias = $aliasResult['dest_alias'];
            $publishDate = $aliasResult['publish_date'];

            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot extract valid date from alias: {$sourceAlias}"
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

            // 准备选项（不包含catid，因为作为独立参数传递）
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
                $destAlias,
                $options,
                $destCategory // 传递目标分类ID
            );

            return [
                'success' => true,
                'action' => $result['action'],
                'id' => $result['id'],
                'url' => $result['url'] ?? '',
                'source_alias' => $sourceAlias,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 处理特殊alias格式
     * 4. devotionals-psalm191225 → mpa-psalm-191225 (2019-12-25)
     * 5. devotionals-dy191203 → dy-daily-bread-191203 (2019-12-03)
     * 6. devotionals-dy-church-calendar-250905 → dy-church-calendar-250905 (2025-09-05)
     * 7. exposition-dy-dy-verses-20250912 → dy250912 (2025-09-12)
     * 8. exposition-ttb-cttb-*-YYYYMMDD → ttbYYMMDD
     *    exposition-ttb-cttb-0001-guide01-20200330 → ttb200330 (2020-03-30)
     *    exposition-ttb-cttb-1254-revelation17-20250116 → ttb250116 (2025-01-16)
     *    exposition-ttb-cttb-0068-genesis59-20200701 → ttb200701 (2020-07-01)
     *    exposition-ttb-cttb-0101-matthew28-20200817 → ttb200817 (2020-08-17)
     *
     * @param string $sourceAlias
     * @return array
     */
    private function processSpecialAlias(string $sourceAlias): array
    {
        // 情况4: devotionals-psalm191225 → mpa-psalm-191225
        if (preg_match('/^devotionals-psalm(\d{6})$/', $sourceAlias, $matches)) {
            $dateString = $matches[1]; // 191225
            $destAlias = "mpa-psalm-{$dateString}";
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from psalm alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 情况5: devotionals-dy191203 → dy-daily-bread-191203
        if (preg_match('/^devotionals-dy(\d{6})$/', $sourceAlias, $matches)) {
            $dateString = $matches[1]; // 191203
            $destAlias = "dy-daily-bread-{$dateString}";
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from dy alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 情况6: devotionals-dy-church-calendar-250905 → dy-church-calendar-250905
        if (preg_match('/^devotionals-dy-church-calendar-(\d{6})$/', $sourceAlias, $matches)) {
            $dateString = $matches[1]; // 250905
            $destAlias = "dy-church-calendar-{$dateString}";
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from church-calendar alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 情况7: exposition-dy-dy-verses-20250912 → dy250912
        if (preg_match('/^exposition-dy-dy-verses-(\d{8})$/', $sourceAlias, $matches)) {
            $fullDateString = $matches[1]; // 20250912
            $dateString = substr($fullDateString, 2); // 250912 (去掉前两位年份)
            $destAlias = "dy{$dateString}";
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from dy-verses alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 情况8: exposition-ttb-cttb-* → ttb250116
        // 匹配所有 exposition-ttb-cttb-XXXX-YYYY-YYYYMMDD 格式，提取最后的日期
        if (preg_match('/^exposition-ttb-cttb-.*-(\d{8})$/', $sourceAlias, $matches)) {
            $fullDateString = $matches[1]; // 20200330 (第1个捕获组是日期)
            $dateString = substr($fullDateString, 2); // 200330 (去掉前两位年份)
            $destAlias = "ttb{$dateString}";
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from ttb alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 情况9: exposition-bs-* → bs250914
        // 匹配 exposition-bs-XXXX-bs250914 格式，提取最后的bs+日期
        if (preg_match('/^exposition-bs-.*-(bs\d{6})$/', $sourceAlias, $matches)) {
            $destAlias = $matches[1]; // bs250914
            $dateString = substr($destAlias, 2); // 250914 (去掉前缀bs)
            $publishDate = $this->parseDateString($dateString);
            
            if (!$publishDate) {
                return [
                    'success' => false,
                    'error' => "Cannot parse date from bs alias: {$sourceAlias} (date: {$dateString})"
                ];
            }
            
            return [
                'success' => true,
                'dest_alias' => $destAlias,
                'publish_date' => $publishDate
            ];
        }
        
        // 如果都不匹配，返回错误
        return [
            'success' => false,
            'error' => "Unsupported alias format: {$sourceAlias}. Expected formats: devotionals-psalm191225, devotionals-dy191203, devotionals-dy-church-calendar-250905, exposition-dy-dy-verses-YYYYMMDD, exposition-ttb-cttb-*-YYYYMMDD, or exposition-bs-*-bs250914"
        ];
    }

    /**
     * 解析日期字符串 YYMMDD 格式
     * 例如：191225 → 2019-12-25 00:00:00
     *      250905 → 2025-09-05 00:00:00
     *
     * @param string $dateString
     * @return string|null
     */
    private function parseDateString(string $dateString): ?string
    {
        if (strlen($dateString) !== 6) {
            return null;
        }
        
        // 解析日期：YYMMDD
        $year = '20' . substr($dateString, 0, 2); // YY → 20YY
        $month = substr($dateString, 2, 2);       // MM
        $day = substr($dateString, 4, 2);         // DD
        
        // 验证日期是否有效
        if (checkdate((int)$month, (int)$day, (int)$year)) {
            return "{$year}-{$month}-{$day} 00:00:00";
        } else {
            Log::warning("Invalid date string", [
                'date_string' => $dateString,
                'year' => $year,
                'month' => $month,
                'day' => $day
            ]);
        }
        
        return null;
    }
}