<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class JoomlaContentService
{
    /**
     * Joomla configuration
     */
    private ?string $joomlaUrl = null;
    private ?string $apiToken = null;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        // 从配置文件或环境变量获取Joomla配置
        $this->joomlaUrl = config('services.joomla.destination.url') ?: env('JOOMLA_DES_URL');
        $this->apiToken = config('services.joomla.destination.api_token') ?: env('JOOMLA_DES_API_TOKEN');
    }

    /**
     * 创建或更新文章
     *
     * @param string $title 文章标题
     * @param string $content 文章内容
     * @param string $alias 文章别名
     * @param array $options 可选参数
     * @param int|null $categoryId 分类ID，如果提供则使用此ID，否则根据alias自动解析
     * @return array 创建或更新结果
     * @throws Exception
     */
    public function createOrUpdateArticle(string $title, string $content, string $alias, array $options = [], ?int $categoryId = null)
    {
        // 检查文章是否已存在
        $existingArticleId = $this->getArticleIdByAlias($alias);
        
        if ($existingArticleId) {
            Log::info('Article exists, updating', [
                'alias' => $alias,
                'existing_id' => $existingArticleId
            ]);
            
            return $this->updateArticle($existingArticleId, $title, $content, $alias, $options, $categoryId);
        } else {
            Log::info('Article does not exist, creating new', [
                'alias' => $alias
            ]);
            
            try {
                return $this->createArticle($title, $content, $alias, $options, $categoryId);
            } catch (Exception $e) {
                // 如果创建失败并且错误信息包含"same alias"，尝试再次查找并更新
                if (str_contains($e->getMessage(), 'same alias') || str_contains($e->getMessage(), 'Another Article')) {
                    Log::warning('Creation failed due to existing alias, attempting to find and update', [
                        'alias' => $alias,
                        'error' => $e->getMessage()
                    ]);
                    
                    // 使用更简单的搜索方法，直接搜索具有特定别名的文章
                    Log::info('Searching for existing article by alias', [
                        'alias' => $alias,
                        'category_id' => $categoryId
                    ]);
                    
                    $existingArticleId = $this->searchArticleByAliasDirectly($alias);
                    
                    if ($existingArticleId) {
                        Log::info('Found existing article with fallback method, updating', [
                            'alias' => $alias,
                            'existing_id' => $existingArticleId
                        ]);
                        
                        return $this->updateArticle($existingArticleId, $title, $content, $alias, $options, $categoryId);
                    }
                }
                
                // 重新抛出原始异常
                throw $e;
            }
        }
    }

    /**
     * 使用备用方法根据alias获取文章ID
     *
     * @param string $alias 文章别名
     * @param int|null $categoryId 分类ID
     * @return int|null 文章ID或null
     */
    public function getArticleIdByAliasWithFallback(string $alias, ?int $categoryId = null): ?int
    {
        try {
            // 尝试通过Joomla API获取文章列表并查找匹配的alias
            $url = "{$this->joomlaUrl}/api/index.php/v1/content/articles";
            $params = [
                'list[limit]' => 30, // 进一步减小页面大小
            ];
            
            // 如果指定了分类，则只在该分类中查找
            if ($categoryId) {
                $params['filter[category_id]'] = $categoryId;
            }
            
            $offset = 0;
            $maxPages = 15; // 进一步减少最大页数限制
            $pageCount = 0;
            
            while ($pageCount < $maxPages) {
                if ($offset > 0) {
                    $params['page[offset]'] = $offset;
                }
                
                $response = Http::timeout(45)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Joomla-Token' => $this->apiToken,
                        'Accept' => '*/*',
                    ])
                    ->get($url, $params);

                if (!$response->successful()) {
                    break;
                }

                $data = $response->json();
                $articles = $data['data'] ?? [];
                
                if (empty($articles)) {
                    break;
                }
                
                // 遍历文章时立即检查并释放内存
                foreach ($articles as $article) {
                    $articleAlias = $article['attributes']['alias'] ?? '';
                    
                    if ($articleAlias === $alias) {
                        $articleId = (int) $article['id'];
                        
                        // 立即释放大量数据
                        unset($articles, $data, $response);
                        
                        return $articleId;
                    }
                    
                    // 释放已处理的文章数据
                    unset($article);
                }
                
                // 检查是否有下一页
                $links = $data['links'] ?? [];
                $hasNext = isset($links['next']);
                
                // 立即释放当前页数据
                $articlesCount = count($articles);
                unset($articles, $data, $response, $links);
                
                if (!$hasNext) {
                    break;
                }
                
                $offset += $articlesCount;
                $pageCount++;
                
                // 强制垃圾回收（在必要时）
                if ($pageCount % 10 === 0) {
                    gc_collect_cycles();
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            // 发生异常时也要清理内存
            gc_collect_cycles();
            return null;
        }
    }

    /**
     * 根据alias获取文章ID
     *
     * @param string $alias 文章别名
     * @return int|null 文章ID或null
     */
    public function getArticleIdByAlias(string $alias): ?int
    {
        try {
            $apiUrl = rtrim($this->joomlaUrl, '/') . '/getArticleIdByAlias.php';
            
            $response = Http::timeout(10)
                ->get($apiUrl, [
                    'alias' => $alias
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['id']) && !empty($data['id'])) {
                    $articleId = (int) $data['id'];
                    // Log::info('Found existing article by custom API', [
                    //     'id' => $articleId,
                    //     'alias' => $alias,
                    //     'api_url' => $apiUrl
                    // ]);
                    
                    return $articleId;
                }
            }
            
            Log::info('No article found with alias via custom API', [
                'alias' => $alias,
                'api_url' => $apiUrl,
                'response' => $response->json()
            ]);
            return null;
            
        } catch (Exception $e) {
            Log::warning('Error checking for existing article via custom API', [
                'alias' => $alias,
                'api_url' => $apiUrl ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 创建文章
     *
     * @param string $title 文章标题
     * @param string $content 文章内容
     * @param string $alias 文章别名
     * @param array $options 可选参数
     * @param int|null $categoryId 分类ID，如果提供则使用此ID，否则根据alias自动解析
     * @return array 创建结果
     * @throws Exception
     */
    public function createArticle(string $title, string $content, string $alias, array $options = [], ?int $categoryId = null)
    {
        // 确定要使用的分类ID
        if ($categoryId !== null) {
            // 使用传入的分类ID
            $finalCategoryId = $categoryId;
        } else {
            // 根据alias获取category ID
            $categories = $this->getCategories();
            $code = substr($alias, 0, -6);
            $finalCategoryId = $categories[$code] ?? 2; // 如果找不到对应的alias，使用默认分类ID 2
        }
        
        $articleData = [
            'title' => $title,
            'alias' => $alias,
            'introtext' => '', // 简介文本，可以为空
            'fulltext' => $content, // 完整文本内容
            'catid' => $finalCategoryId,
            'language' => $options['language'] ?? '*',
            'metadesc' => $options['meta_desc'] ?? '',
            'metakey' => $options['meta_keys'] ?? '',
            'state' => (int) ($options['state'] ?? 1),
            'featured' => (int) ($options['featured'] ?? 0),
            'access' => (int) ($options['access'] ?? 1),
        ];

        // 添加发布时间（必须设置，如果没有提供则使用当前时间）
        if (isset($options['publish_up']) && !empty($options['publish_up'])) {
            $articleData['publish_up'] = $options['publish_up'];
        } else {
            // 如果没有提供发布时间，使用当前时间
            $articleData['publish_up'] = date('Y-m-d H:i:s');
        }

        Log::info('Creating Joomla article', [
            'alias' => $alias,
            'publish_up' => $articleData['publish_up']
        ]);
        
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Joomla-Token' => $this->apiToken,
                'Accept' => '*/*',
            ])
            ->post("{$this->joomlaUrl}/api/index.php/v1/content/articles", $articleData);

        if ($response->successful()) {
            $result = $response->json();
            
            // 构建文章URL
            $articleUrl = rtrim($this->joomlaUrl, '/') . '/index.php/?option=com_content&view=article&id=' . $result['data']['id'];
            
            // Log::info('Article created successfully', [
            //     'article_id' => $result['data']['id'],
            //     'alias' => $result['data']['attributes']['alias'],
            //     'url' => $articleUrl,
            //     'publish_up' => $articleData['publish_up']
            // ]);
            
            return [
                'action' => 'created',
                'id' => $result['data']['id'],
                'alias' => $result['data']['attributes']['alias'],
                'url' => $articleUrl,
                'data' => $result['data']
            ];
        } else {
            Log::error('Failed to create article', [
                'status' => $response->status(),
                'response' => $response->body(),
                'alias' => $alias
            ]);
            
            throw new Exception('Failed to create article: ' . $response->body());
        }
    }

    /**
     * 更新文章
     *
     * @param int $articleId 文章ID
     * @param string $title 文章标题
     * @param string $content 文章内容
     * @param string $alias 文章别名
     * @param array $options 可选参数
     * @param int|null $categoryId 分类ID，如果提供则使用此ID，否则根据alias自动解析
     * @return array 更新结果
     * @throws Exception
     */
    public function updateArticle(int $articleId, string $title, string $content, string $alias, array $options = [], ?int $categoryId = null)
    {
        // 确定要使用的分类ID
        if ($categoryId !== null) {
            // 使用传入的分类ID
            $finalCategoryId = $categoryId;
        } else {
            // 根据alias获取category ID
            $categories = $this->getCategories();
            $code = substr($alias, 0, -6);
            $finalCategoryId = $categories[$code] ?? 2; // 如果找不到对应的alias，使用默认分类ID 2
        }
        
        $articleData = [
            'title' => $title,
            'alias' => $alias,
            'introtext' => '', // 简介文本，可以为空
            'fulltext' => $content, // 完整文本内容
            'catid' => $finalCategoryId,
            'language' => $options['language'] ?? '*',
            'metadesc' => $options['meta_desc'] ?? '',
            'metakey' => $options['meta_keys'] ?? '',
            'state' => (int) ($options['state'] ?? 1),
            'featured' => (int) ($options['featured'] ?? 0),
            'access' => (int) ($options['access'] ?? 1),
        ];

        // 添加发布时间（必须设置，如果没有提供则使用当前时间）
        if (isset($options['publish_up']) && !empty($options['publish_up'])) {
            $articleData['publish_up'] = $options['publish_up'];
        } else {
            // 如果没有提供发布时间，使用当前时间
            $articleData['publish_up'] = date('Y-m-d H:i:s');
        }

        // Log::info('Updating Joomla article', [
        //     'article_id' => $articleId,
        //     'title' => $title,
        //     'alias' => $alias,
        //     'publish_up' => $articleData['publish_up'],
        //     'data' => $articleData
        // ]);
        
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Joomla-Token' => $this->apiToken,
                'Accept' => '*/*',
            ])
            ->patch("{$this->joomlaUrl}/api/index.php/v1/content/articles/{$articleId}", $articleData);

        if ($response->successful()) {
            $result = $response->json();
            
            // 构建文章URL
            $articleUrl = rtrim($this->joomlaUrl, '/') . '/index.php/?option=com_content&view=article&id=' . $articleId;
            
            // Log::info('Article updated successfully', [
            //     'article_id' => $articleId,
            //     'alias' => $alias,
            //     'url' => $articleUrl,
            //     'publish_up' => $articleData['publish_up']
            // ]);
            
            return [
                'action' => 'updated',
                'id' => $articleId,
                'alias' => $alias,
                'url' => $articleUrl,
                'data' => $result['data'] ?? []
            ];
        } else {
            Log::error('Failed to update article', [
                'article_id' => $articleId,
                'status' => $response->status(),
                'response' => $response->body(),
                'alias' => $alias
            ]);
            
            throw new Exception('Failed to update article: ' . $response->body());
        }
    }

    /**
     * 获取可用分类列表（以alias为key，id为value的数组）
     *
     * @return array
     */
    public function getCategories(): array
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'X-Joomla-Token' => $this->apiToken,
                'Accept' => '*/*',
            ])
            ->get("{$this->joomlaUrl}/api/index.php/v1/content/categories");

        if ($response->successful()) {
            $data = $response->json();
            $categories = $data['data'] ?? [];
            
            // 将数组重组为以alias为key，id为value的格式
            $result = [];
            foreach ($categories as $category) {
                // 获取分类的alias和id
                $alias = $category['attributes']['alias'] ?? null;
                $categoryId = $category['id'] ?? null;
                
                // 确保alias和id都存在才添加到结果中
                if ($alias && $categoryId) {
                    $result[$alias] = (int) $categoryId;
                }
            }
            
            // Log::info('Categories fetched successfully', [
            //     'count' => count($result),
            //     'categories' => $result
            // ]);
            
            return $result;
        }

        Log::warning('Failed to fetch categories', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        
        return [];
    }

    /**
     * 直接通过别名搜索文章（使用搜索API）
     *
     * @param string $alias
     * @return int|null
     */
    public function searchArticleByAliasDirectly(string $alias): ?int
    {
        try {
            // 使用Joomla的搜索API直接查找具有特定别名的文章
            $url = "{$this->joomlaUrl}/api/index.php/v1/content/articles";
            $params = [
                'filter[search]' => $alias,
                'list[limit]' => 10
            ];
            
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Joomla-Token' => $this->apiToken,
                    'Accept' => '*/*',
                ])
                ->get($url, $params);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $articles = $data['data'] ?? [];
            
            // 查找精确匹配的别名
            foreach ($articles as $article) {
                $articleAlias = $article['attributes']['alias'] ?? '';
                
                if ($articleAlias === $alias) {
                    $articleId = (int) $article['id'];
                    
                    Log::info('Found existing article by direct search', [
                        'alias' => $alias,
                        'article_id' => $articleId
                    ]);
                    
                    return $articleId;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::warning('Error in direct article search', [
                'alias' => $alias,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}