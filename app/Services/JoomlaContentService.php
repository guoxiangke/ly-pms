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
    private string $joomlaUrl;
    private string $apiToken;

    /**
     * Create a new service instance.
     */
    public function __construct()
    {
        // 从配置文件或环境变量获取Joomla配置
        $this->joomlaUrl = config('services.joomla.destination.url') ?: env('JOOMLA_DES_URL');
        $this->apiToken = config('services.joomla.destination.api_token') ?: env('JOOMLA_DES_API_TOKEN');
        
        if (!$this->joomlaUrl || !$this->apiToken) {
            throw new Exception('Joomla URL and API Token must be configured');
        }
    }

    /**
     * 创建或更新文章
     *
     * @param string $title 文章标题
     * @param string $content 文章内容
     * @param string $alias 文章别名
     * @param array $options 可选参数
     * @return array 创建或更新结果
     * @throws Exception
     */
    public function createOrUpdateArticle(string $title, string $content, string $alias, array $options = [])
    {
        // 检查文章是否已存在
        $existingArticleId = $this->getArticleIdByAlias($alias);
        
        if ($existingArticleId) {
            Log::info('Article exists, updating', [
                'alias' => $alias,
                'existing_id' => $existingArticleId
            ]);
            
            return $this->updateArticle($existingArticleId, $title, $content, $alias, $options);
        } else {
            Log::info('Article does not exist, creating new', [
                'alias' => $alias
            ]);
            
            return $this->createArticle($title, $content, $alias, $options);
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
                    Log::info('Found existing article by custom API', [
                        'id' => $articleId,
                        'alias' => $alias,
                        'api_url' => $apiUrl
                    ]);
                    
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
     * @return array 创建结果
     * @throws Exception
     */
    public function createArticle(string $title, string $content, string $alias, array $options = [])
    {
        // 根据alias获取category ID
        $categories = $this->getCategories();
        $code = substr($alias, 0, -6);
        $categoryId = $categories[$code] ?? 2; // 如果找不到对应的alias，使用默认分类ID 2
        
        $articleData = [
            'title' => $title,
            'alias' => $alias,
            'introtext' => '', // 简介文本，可以为空
            'fulltext' => $content, // 完整文本内容
            'catid' => $categoryId,
            'language' => $options['language'] ?? '*',
            'metadesc' => $options['meta_desc'] ?? '',
            'metakey' => $options['meta_keys'] ?? '',
            'state' => (int) ($options['state'] ?? 1),
            'featured' => (int) ($options['featured'] ?? 0),
            'access' => (int) ($options['access'] ?? 1),
        ];

        Log::info('Creating Joomla article', [
            'title' => $title,
            'alias' => $alias,
            'data' => $articleData
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
            
            Log::info('Article created successfully', [
                'article_id' => $result['data']['id'],
                'alias' => $result['data']['attributes']['alias'],
                'url' => $articleUrl
            ]);
            
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
                'data' => $articleData
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
     * @return array 更新结果
     * @throws Exception
     */
    public function updateArticle(int $articleId, string $title, string $content, string $alias, array $options = [])
    {
        // 根据alias获取category ID
        $categories = $this->getCategories();
        $code = substr($alias, 0, -6);
        $categoryId = $categories[$code] ?? 2;
        
        $articleData = [
            'title' => $title,
            'alias' => $alias,
            'introtext' => '', // 简介文本，可以为空
            'fulltext' => $content, // 完整文本内容
            'catid' => $categoryId,
            'language' => $options['language'] ?? '*',
            'metadesc' => $options['meta_desc'] ?? '',
            'metakey' => $options['meta_keys'] ?? '',
            'state' => (int) ($options['state'] ?? 1),
            'featured' => (int) ($options['featured'] ?? 0),
            'access' => (int) ($options['access'] ?? 1),
        ];

        Log::info('Updating Joomla article', [
            'article_id' => $articleId,
            'title' => $title,
            'alias' => $alias,
            'data' => $articleData
        ]);
        
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
            
            Log::info('Article updated successfully', [
                'article_id' => $articleId,
                'alias' => $alias,
                'url' => $articleUrl
            ]);
            
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
                'data' => $articleData
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
            
            Log::info('Categories fetched successfully', [
                'count' => count($result),
                'categories' => $result
            ]);
            
            return $result;
        }

        Log::warning('Failed to fetch categories', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        
        return [];
    }
}