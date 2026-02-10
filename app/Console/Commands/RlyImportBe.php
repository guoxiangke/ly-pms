<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Services\JoomlaContentService;

class RlyImportBe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-rly-be';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import BE content from CSV files in storage/app/export';

    /**
     * Joomla configuration
     */
    private ?string $joomlaUrl = null;
    private ?string $apiToken = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        // 使用你提供的Joomla配置
        // $this->joomlaUrl = 'http://138';
        // $this->apiToken = 'c2hhMjU22ZDA4NzIx';

    /**
     * Get all tags from Joomla API
     *
     * @return array Tag ID to title mapping
     */
    private function getAllTags(): array
    {
        // 返回硬编码的tags映射：圣经经卷名 => tag_id
        return ["创世记"=>10,
            "出埃及记"=>11,
            "利未记"=>12,
            "民数记"=>13,
            "申命记"=>14,
            "约书亚记"=>15,
            "士师记"=>16,
            "路得记"=>17,
            "撒母耳记上"=>18,
            "撒母耳记下"=>19,
            "列王纪上"=>20,
            "列王纪下"=>21,
            "列王纪上、下"=>21,//
            "历代志上"=>22,
            "历代志下"=>23,
            "以斯拉记"=>24,
            "尼希米记"=>25,
            "以斯帖记"=>26,
            "约伯记"=>27,
            "诗篇"=>28,
            "箴言"=>29,
            "传道书"=>30,
            "雅歌"=>31,
            "以赛亚书"=>32,
            "耶利米书"=>33,
            "耶利米哀歌"=>34,
            "以西结书"=>35,
            "但以理书"=>36,
            "何西阿书"=>37,
            "约珥书"=>38,
            "阿摩司书"=>39,
            "俄巴底亚书"=>40,
            "约拿书"=>41,
            "弥迦书"=>42,
            "那鸿书"=>43,
            "哈巴谷书"=>44,
            "西番雅书"=>45,
            "哈该书"=>46,
            "撒迦利亚书"=>47,
            "玛拉基书"=>48,
            "马太福音"=>49,
            "马可福音"=>50,
            "路加福音"=>51,
            "约翰福音"=>52,
            "使徒行传"=>53,
            "罗马书"=>54,
            "哥林多前书"=>55,
            "哥林多后书"=>56,
            "加拉太书"=>57,
            "以弗所书"=>58,
            "腓立比书"=>59,
            "歌罗西书"=>60,
            "帖撒罗尼迦前书"=>61,
            "帖撒罗尼迦后书"=>62,
            "提摩太前书"=>63,
            "提摩太后书"=>64,
            "提多书"=>65,
            "腓利门书"=>66,
            "希伯来书"=>67,
            "雅各书"=>68,
            "彼得前书"=>69,
            "彼得后书"=>70,
            "彼得前后书"=>69,//
            "约翰一书"=>71,
            "约翰二书"=>72,
            "约翰三书"=>73,
            "约翰一二三书"=>73,//
            "犹大书"=>74,
            "启示录"=>75];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Joomla URL: {$this->joomlaUrl}");
        $this->info("API Token: " . substr($this->apiToken, 0, 20) . "...");
        
        // 初始化JoomlaContentService
        $joomlaService = new JoomlaContentService();
        
        // 通过反射设置私有属性，使用当前命令中的配置
        $reflection = new \ReflectionClass($joomlaService);
        $joomlaUrlProperty = $reflection->getProperty('joomlaUrl');
        $joomlaUrlProperty->setAccessible(true);
        $joomlaUrlProperty->setValue($joomlaService, $this->joomlaUrl);
        
        $apiTokenProperty = $reflection->getProperty('apiToken');
        $apiTokenProperty->setAccessible(true);
        $apiTokenProperty->setValue($joomlaService, $this->apiToken);
        
        // 首先获取所有tags
        $tags = $this->getAllTags();
        
        if (empty($tags)) {
            $this->error('未能获取到tags，无法继续导入');
            return 1;
        }
        $this->info('开始读取 storage/app/export 目录中的 CSV 文件...');
        
        // 获取 storage/app/export 目录中的所有 CSV 文件，包括子目录
        $exportPath = storage_path('app/export');
        $csvFiles = [];
        
        // 使用递归查找所有CSV文件
        $directoryIterator = new \RecursiveDirectoryIterator($exportPath);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'csv') {
                $csvFiles[] = $file->getPathname();
            }
        }
        
        if (empty($csvFiles)) {
            $this->error('在 storage/app/export 目录中未找到 CSV 文件');
            return 1;
        }
        
        $this->info('找到 ' . count($csvFiles) . ' 个 CSV 文件');
        
        foreach ($csvFiles as $csvFile) {
            $this->info('处理文件: ' . basename($csvFile));
            
            try {
                // 打开 CSV 文件
                $handle = fopen($csvFile, 'r');
                if ($handle === false) {
                    throw new \Exception('无法打开文件: ' . $csvFile);
                }
                
                // 读取标题行
                $headers = fgetcsv($handle);
                if ($headers === false) {
                    fclose($handle);
                    throw new \Exception('无法读取 CSV 标题行');
                }
                
                // 查找"页面URL"列的索引
                $urlColumnIndex = array_search('页面URL', $headers);
                if ($urlColumnIndex === false) {
                    $this->warn('在文件 ' . basename($csvFile) . ' 中未找到"页面URL"列');
                    fclose($handle);
                    continue;
                }
                
                $this->info('在文件 ' . basename($csvFile) . ' 中找到"页面URL"列，索引: ' . $urlColumnIndex);
                
                $rowCount = 0;
                // 读取数据行 - 只处理第一行
                while (($row = fgetcsv($handle)) !== false) {
                    $rowCount++;
                    if ($rowCount > 1) {
                        //break; // 只处理第一行
                    }
                    
                    if (isset($row[$urlColumnIndex]) && !empty($row[$urlColumnIndex])) {
                        $url = $row[$urlColumnIndex];
                        
                        // 替换 URL 前缀
                        $url = str_replace('https://r.729ly.net/exposition/exposition-be/', '', $url);
                        $url = str_replace('https://r.729ly.net/exposition-be/', '', $url);
                        
                        // 处理带 -home 的路径
                        $parts = explode('/', $url);
                        if (count($parts) > 1 && str_contains($parts[0], '-home')) {
                            // 去掉第一级路径（包含 -home 的部分）
                            array_shift($parts);
                            //
                        }
                        // 对parts进行处理，如果 count($parts) >2,退出，并给出日志。
                        // 然后处理 $parts[0] 和 $parts[1];
                        // $parts[0] 的处理逻辑是：$parts0 = explode('-', $url); 获取所有部分，然后取最后一个。如：
                        // exposition-be-ot-poetic-books-proverbs => proverbs

                        // 但如果最后一个结尾是：one two three 的话，需要往前再取一个。
                        // 特例举例：
                        // exposition-be-ot-history-judges-seriestwo => judges-seriestwo
                        // exposition-be-nt-epistles-of-paul-corinthians-one => corinthians-one
                        // exposition-be-nt-general-epistles-peter-one-two => peter-one-two
                        // exposition-be-ot-history-samuel-two => samuel-two
                        // 最特别的是：exposition-be-nt-general-epistles-john-one-two-three/ =》  john-one-two-three 是我们想要的。

                        // 检查parts数量
                        if (count($parts) > 2) {
                            Log::warning('URL路径部分过多，跳过处理', [
                                'url' => $url,
                                'parts' => $parts,
                                'parts_count' => count($parts)
                            ]);
                            continue;
                        }
                        
                        // 初始化 keyPart
                        $keyPart = '';
                        
                        // 处理 $parts[0]
                        if (isset($parts[0])) {
                            $part0 = $parts[0];
                            $part0Parts = explode('-', $part0);
                            
                            // 定义数字词和系列词
                            $numberWords = ['one', 'two', 'three', 'seriestwo', 'seriesone'];
                            
                            // 从后往前收集部分
                            $collectedParts = [];
                            
                            // 先收集最后一个部分
                            $lastIndex = count($part0Parts) - 1;
                            $lastPart = $part0Parts[$lastIndex];
                            
                            // 检查最后一个部分是否是数字词或包含数字词
                            $lastIsNumberWord = in_array($lastPart, $numberWords);
                            $lastContainsNumberWord = false;
                            foreach ($numberWords as $numWord) {
                                if (str_contains($lastPart, $numWord)) {
                                    $lastContainsNumberWord = true;
                                    break;
                                }
                            }
                            
                            if ($lastIsNumberWord || $lastContainsNumberWord) {
                                // 如果最后一个部分是数字词，需要往前收集更多部分
                                // 收集直到遇到不是数字词的部分
                                for ($i = $lastIndex; $i >= 0; $i--) {
                                    $currentPart = $part0Parts[$i];
                                    $currentIsNumberWord = in_array($currentPart, $numberWords);
                                    $currentContainsNumberWord = false;
                                    foreach ($numberWords as $numWord) {
                                        if (str_contains($currentPart, $numWord)) {
                                            $currentContainsNumberWord = true;
                                            break;
                                        }
                                    }
                                    
                                    array_unshift($collectedParts, $currentPart);
                                    
                                    // 如果当前部分既不是数字词也不包含数字词，且不是最后一个部分，则停止
                                    if (!$currentIsNumberWord && !$currentContainsNumberWord && $i < $lastIndex) {
                                        break;
                                    }
                                }
                            } else {
                                // 如果最后一个部分不是数字词，只取最后一个部分
                                $collectedParts = [$lastPart];
                            }
                            
                            $keyPart = implode('-', $collectedParts);
                            
                            // 移除常见前缀
                            $prefixes = ['exposition-be-', 'exposition-', 'be-'];
                            foreach ($prefixes as $prefix) {
                                if (str_starts_with($keyPart, $prefix)) {
                                    $keyPart = substr($keyPart, strlen($prefix));
                                    break;
                                }
                            }
                            
                            // 更新 parts[0] 为处理后的 keyPart
                            $parts[0] = $keyPart;
                            
                            // Log::info("提取的关键部分: {$keyPart}");
                            
                            // 这里可以将 keyPart 映射到 tag
                            // 例如：proverbs -> 箴言, samuel-two -> 撒母耳记下 等
                        }
                        
                        // 处理 $parts[1]（如果有）
                        if (isset($parts[1])) {
                            $part1 = $parts[1];
                            
                            // 提取数字后缀
                            $suffix = '';
                            if (preg_match('/(\d+)$/', $part1, $matches)) {
                                $suffix = $matches[1];
                            }
                            
                            // 构建新的 part1：keyPart + 数字后缀
                            $newPart1 = $keyPart . $suffix;
                            $parts[1] = $newPart1;
                            
                            // Log::info("第二部分处理: {$part1} => {$newPart1}");
                        }

                        // 给所有部分添加 be- 前缀
                        foreach ($parts as &$part) {
                            if (!str_starts_with($part, 'be-')) {
                                $part = 'be-' . $part;
                            }
                        }
                        unset($part);

                        $url = implode('/', $parts);
                        
                        Log::info($url);
                        
                        // 获取文章标题、mp3地址和内容
                        $title = $row[0] ?? ''; // 文章标题列
                        $mp3Url = $row[6] ?? ''; // mp3地址列（第7列，索引6）
                        $content = $row[7] ?? ''; // 文章正文列

                        // 改进版：正确匹配空P标签和&#13;
                        $content = preg_replace('/^(?:\s*<p>\s*(?:&nbsp;|&#160;|&#13;|\s)*<\/p>\s*(?:&#13;)?\s*)*(?=<p>)/u', '', $content);
                        
                        // 在文章内容开头添加audio HTML标签
                        if (!empty($mp3Url)) {
                            $audioHtml = '<audio controls="" src="' . $mp3Url . '" class="audio audio-be-import" data-alias="' . $url . '"  width="100%"></audio>';
                            // 移除audio和content之间的空行
                            $content = $audioHtml . $content;
                        }
                        
                        if (!empty($title) && !empty($content)) {
                            // 根据CSV文件名获取tag_id
                            $csvFileName = basename($csvFile, '.csv');
                            $tagId = $tags[$csvFileName] ?? null;
                            
                            if ($tagId) {
                                // 构建alias：使用完整的URL路径（如be-genesis/be-genesis01）
                                $articleAlias = end($parts);
                                
                                $this->info("导入文章: {$title}");
                                $this->info("Alias: {$articleAlias}");
                                $this->info("Tag ID: {$tagId}");
                                
                                try {
                                    // 创建文章
                                    $result = $joomlaService->createOrUpdateArticle(
                                        $title,
                                        $content,
                                        $articleAlias,
                                        [], // options
                                        11, // categoryId固定为11
                                        [$tagId] // tags数组格式
                                    );
                                    
                                    $this->info("文章导入成功! ID: {$result['id']}, 操作: {$result['action']}");
                                    Log::info("文章导入成功", [
                                        'article_id' => $result['id'],
                                        'alias' => $articleAlias,
                                        'title' => $title,
                                        'action' => $result['action']
                                    ]);
                                    
                                } catch (\Exception $e) {
                                    $this->error("文章导入失败: " . $e->getMessage());
                                    Log::error("文章导入失败", [
                                        'alias' => $articleAlias,
                                        'title' => $title,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            } else {
                                $this->warn("未找到CSV文件对应的tag_id: {$csvFileName}");
                                Log::warning("未找到CSV文件对应的tag_id", [
                                    'csv_file' => $csvFileName,
                                    'available_tags' => array_keys($tags)
                                ]);
                            }
                        } else {
                            $this->warn("第 {$rowCount} 行: 标题或内容为空，跳过导入");
                        }
                    } else {
                        $this->warn('第 ' . $rowCount . ' 行: 页面URL 列为空');
                    }
                }
                
                fclose($handle);
                $this->info(basename($csvFile) . ' 处理完成，共处理 ' . $rowCount . ' 行数据');
                
            } catch (\Exception $e) {
                $this->error('处理文件 ' . basename($csvFile) . ' 时出错: ' . $e->getMessage());
                Log::error('处理 CSV 文件出错: ' . $e->getMessage(), [
                    'file' => $csvFile,
                    'error' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->info('所有 CSV 文件处理完成');
        return 0;
    }
}

// 保留原有的配置数据
$breadcrumbs = [
    "创"     => "ot-pentateuch-genesis",
    // https://r.729ly.net/exposition/exposition-be/exposition-be-ot-pentateuch-genesis/exposition-be-ot-pentateuch-genesis01
    "出"     => "ot-pentateuch-exodus",
    "利"     => "ot-pentateuch-leviticus-home",
    // https://r.729ly.net/exposition/exposition-be/exposition-be-ot-pentateuch-leviticus-home/exposition-be-ot-pentateuch-leviticus/exposition-be-ot-pentateuch-leviticus01
    "民"     => "ot-pentateuch-numbers-home",
    "申"     => "ot-pentateuch-deuteronomy",
    "书"     => "ot-history-joshua-home",
    "士"     => "ot-history-judges-home",
    "得"     => "ot-history-ruth",
    "撒上"   => "ot-history-samuel-one",
    "撒下"   => "ot-history-samuel-two",
    "撒上下" => "ot-history-samuel-one-two",
    "王上下" => "ot-history-kings-one-two",
    "代上"   => "ot-history-chronicles-one",
    "代下"   => "ot-history-chronicles-two",
    "拉"     => "ot-history-ezra-home",
    "尼"     => "ot-history-nehemiah-home",
    "斯"     => "ot-history-esther",
    "伯"     => "ot-poetic-books-job",
    "诗"     => "ot-poetic-books-psalms-home",
    "箴"     => "ot-poetic-books-proverbs",
    "传"     => "ot-poetic-books-ecclesiastes-home",
    "歌"     => "ot-poetic-books-songofsolomon",
    "赛"     => "ot-major-prophets-isaiah",
    "耶"     => "ot-major-prophets-jeremiah",
    "哀"     => "ot-major-prophets-lamentations",
    "结"     => "ot-major-prophets-ezekiel",
    "但"     => "ot-major-prophets-daniel",
    "何"     => "ot-minor-prophets-hosea",
    "珥"     => "ot-minor-prophets-joel",
    "摩"     => "ot-minor-prophets-amos-home",
    "俄"     => "ot-minor-prophets-obadiah",
    "拿"     => "ot-minor-prophets-jonah",
    "弥"     => "ot-minor-prophets-micah",
    "鸿"     => "ot-minor-prophets-nahum",
    "哈"     => "ot-minor-prophets-habakkuk",
    "番"     => "ot-minor-prophets-zephaniah",
    "该"     => "ot-minor-prophets-haggai",
    "亚"     => "ot-minor-prophets-zechariah",
    "玛"     => "ot-minor-prophets-malachi",
    "太"     => "nt-gospels-matthew-home",
    "可"     => "nt-gospels-mark",
    "路"     => "nt-gospels-luke",
    "约"     => "nt-gospels-john-home",
    "徒"     => "nt-history-acts",
    "罗"     => "nt-epistles-of-paul-romans-home",
    "林前"   => "nt-epistles-of-paul-corinthians-one",
    "林后"   => "nt-epistles-of-paul-corinthians-two-home",
    "加"     => "nt-epistles-of-paul-galatians",
    "弗"     => "nt-epistles-of-paul-ephesians-home",
    "腓"     => "nt-epistles-of-paul-philippians-home",
    "西"     => "nt-epistles-of-paul-colossians",
    "帖前"   => "nt-epistles-of-paul-thessalonians-one",
    "帖后"   => "nt-epistles-of-paul-thessalonians-two",
    "提前"   => "nt-epistles-of-paul-timothy-one-home",
    "提后"   => "nt-epistles-of-paul-timothy-two-home",
    "多"     => "nt-epistles-of-paul-titus-home",
    "门"     => "nt-epistles-of-paul-philemon-home",
    "来"     => "nt-general-epistles-hebrews-home",
    "雅"     => "nt-general-epistles-james",
    "彼前"   => "nt-general-epistles-peter-one",
    "彼前后" => "nt-general-epistles-peter-one-two",
    "约壹贰叁"      => "nt-general-epistles-john-one-two-three-home",
    "犹"     => "nt-general-epistles-jude-home",
    "启"     => "nt-prophecy-revelation"
    ];


// /be-gen/
// https://jmjy2.work/be-numbers/be241105


// https://r.729ly.net/exposition/exposition-be/exposition-be-ot-pentateuch-leviticus-home/exposition-be-ot-pentateuch-leviticus/exposition-be-ot-pentateuch-leviticus01

// =》 https://jmjy2.work/be-leviticus/be-leviticus01

// https://r.729ly.net/exposition/exposition-be/exposition-be-nt-epistles-of-paul-corinthians-two-home/exposition-be-nt-epistles-of-paul-corinthians-two/exposition-be-nt-epistles-of-paul-corinthians-two01

// =》https://jmjy2.work/be-corinthians-two/be-corinthians-two01



        