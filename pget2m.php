<?php

// 命令行参数列表，用于初始化配置
$param_list = [
    '',
    '--start-url=http://www.tdbat.com/',
    '--sub-string=<div class="panel-body">|<div class="panel-heading title">',
    '--directory-prefix=C:\\workspace\\wwwcrawler',
    '--reject-regex=\?|#|&|(\.rar)|(\.zip)|(\.epub)|(\.txt)|(\.pdf)',
    '--wait=2',
    '--max-threads=10',
    '--no-verbose',
    '--recursive',
    '--no-clobber',
    '--page-requisites',
    '--no-check-certificate',
    '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    '--tries=100',
    '--output-file=pget2.log',
    '--store-database',
    '--pause-time=2000',
    '--pause-tries=5',
];

/* 
 * Pget2m 并发爬虫（MySQL）
 * 
提示：注意看注释哦！

 * 中文说明：
 * 
 * 
 * 支持HTTP/HTTPS/FTP下载，使用数组分片存储链接
 * 版本：3.0 (并发版)
 * 2025-08-05 增加创建目录失败的处理（Windows下存在同名文件时创建目录失败）
 * 
 * 用法示例：
 * php pget.php --recursive --adjust-extension --restrict-file-names --no-check-certificate --tries=10 --wait=0.5 --save-cookies="cookie" --user-agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0" --reject-regex="\?|#|&|(?:\.rar)|(?:\.zip)|(?:\.epub)|(?:\.txt)|(?:\.pdf)" --reject="woff,jpg,png,webp" --accept="html,js,css" --sub-string="<p id=\"b\">,<p class=\"a b\">|</p>,</p>" https://domain/
 * php pget.php https://domain/link
 * php pget.php --input-file="urls.txt"
 * 
 * Pget 使用 pdo_sqlite 记录请求过的链接。
 * 
 * 参数说明：
 *   url                  请求的URL，或用 --start-url="domain"
 *   --mirror             镜像整个网站。0表示只下载单个URL
 *   --input-file         包含URL的文件路径，每行一个URL，批量下载
 *   --no-clobber         不覆盖已存在的本地文件，否则覆盖
 *   --directory-prefix   文件保存目录，默认以主机名为子目录
 *   --reject             拒绝的文件后缀，逗号分隔
 *   --accept             接受的文件后缀，逗号分隔
 *   --accept-regex       接受URL的正则表达式
 *   --reject-regex       拒绝URL的正则表达式
 *   --sub-string         内容截取，"|"分隔开始结束标记，","分隔多组，并且标记按顺序对应。含空格或引号需转义。Pget独有的开关
 *   --wait               每次操作间隔秒数（或微秒）
 *   --no-verbose         不输出日志信息
 *   --utf-8              转为UTF-8编码，默认开启
 *   --recursive          递归下载页面内所有链接，默认关闭
 *   --page-requisites    下载页面依赖的图片、CSS、JS等资源，默认关闭
 *   --directory-prefix   文件保存目录，默认当前目录
 *   --no-parent          递归时不向上级目录爬取
 *   --span-hosts         递归时允许跨主机爬取
 *   --domains            允许爬取的域名，逗号分隔，不自动开启-H
 *   --adjust-extension   HTML文件无.html后缀时自动补全
 *   --restrict-file-names  转义非ASCII和特殊字符，兼容Win/Linux路径
 *   --output-file    输出日志到文件
 *   --save-cookies，--load-cookies       写入和载入cookies，配置一个即可，目前它们公用一个文件，因此不要配置成不同的文件名
 *   --force-directories    强制创建目录结构，默认开启
 *   --no-directories     递归检索时不要创建目录层次结构
 *   --tries             重试次数，默认20次 
 *   --retry-connrefused    强制重试连接被拒绝的请求，默认开启
 *   --remote-encoding  远程编码，默认UTF-8
 *   --local-encoding  本地编码，默认UTF-8
 *   --level             递归深度，默认5级
 *   --pause-tries  最多允许暂停次数
 *   --pause-interval  两次暂停最小间隔（秒）
 *   --pause-time  每次暂停时长（秒）
 * 
 */

if (php_sapi_name() !== 'cli') die("This script can only be run in CLI mode.\n"); // 检查是否在命令行模式下运行
if (version_compare(PHP_VERSION, '8.0.0', '<')) die("PHP 8.0+ required.\n"); // PHP版本检查
if (!extension_loaded('pdo_sqlite')) echo "pdo_sqlite extension is not enabled. logs wiil not be using.\n"; // 检查 PDO SQLite 是否可用
error_reporting(E_ALL & ~E_NOTICE); // 只显示除了通知之外的所有错误
set_time_limit(0); // 设置脚本执行时间无限制
ignore_user_abort(1); // 忽略用户断开连接，确保脚本继续执行
ini_set('memory_limit', '20480M'); // 设置脚本可使用的最大内存为20480M
date_default_timezone_set('Asia/Shanghai'); // 设置时区为亚洲上海
register_shutdown_function('pget_shutdown_handler'); // 致命错误兜底
// 中止信号兜底（windows 不支持 pcntl_signal ）
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function () {
        pget_signal_handler('中断信号', 'SIGINT', 130);
    });
    pcntl_signal(SIGTERM, function () {
        pget_signal_handler('终止信号', 'SIGTERM', 143);
    });
}
// =================== 主流程 ===================
try {
    // 命令行参数正常的话，使用命令行参数
    if (count($argv) > 2) {
        $param_list = $argv;
    }
    // 创建PgetConfig对象，传入命令行参数进行配置初始化
    $config = new PgetConfig($param_list);
    // 创建Pget对象，传入配置对象\工具对象、爬虫对象
    $pget = new Pget($config);
    // 启动主流程
    $pget->run();
    $pget->close();
    unset($pget);
} catch (\Throwable $e) {
    file_put_contents(__DIR__ . '/pget_shutdown.log', "[EXCEPTION] " . date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    throw $e;
}
// =================== 配置类 ===================
class PgetConfig
{
    // 配置选项
    public array $options = [
        '--sub-string' => '',
        '--store-database' => 0,
        '--mirror' => 0,
        '--recursive' => 0,
        '--input-file' => '',
        '--no-clobber' => 0,
        '--start-url' => '',
        '--directory-prefix' => '',
        '--reject' => '',
        '--accept' => '',
        '--reject-regex' => '',
        '--accept-regex' => '',
        '--wait' => 0,
        '--no-verbose' => 0,
        '--page-requisites' => 0,
        '--utf-8' => 0,
        '--span-hosts' => '',
        '--domains' => '',
        '--adjust-extension' => 0,
        '--restrict-file-names' => 0,
        '--no-parent' => 0,
        '--no-check-certificate' => 0,
        '--user-agent' => 'Pget/2.0',
        '--header' => '',
        '--save-cookies' => '',
        '--load-cookies' => '',
        '--keep-session-cookies' => 0,
        '--output-file' => '',
        '--force-directories' => 0,
        '--tries' => 20,
        '--retry-connrefused' => 0,
        '--level' => 5,
        '--random-wait' => 0,
        '--no-cache' => 0,
        '--max-threads' => 0,
        '--pause-time' => 0,
        '--pause-interval' => 0,
        '--pause-tries' => 0
    ];
    public bool $isWindows = false;
    public bool $isChineseWindows = false;
    public array $start_info = [];
    public array $sub_string_rules = [];

    /**
     * 构造函数，解析命令行参数，初始化配置
     */
    public function __construct($param_list)
    {
        // 判断当前操作系统是否为Windows
        $this->isWindows = stripos(PHP_OS, 'WIN') === 0;
        if ($this->isWindows) {
            $chcp = @shell_exec('chcp');
            if ($chcp && (strpos($chcp, '936') !== false || strpos($chcp, '65001') !== false)) {
                $this->isChineseWindows = true;
            }
        }
        // 遍历命令行参数，从第二个参数开始（第一个参数是脚本文件名）
        for ($i = 1; $i < count($param_list); $i++) {
            // 检查参数是否包含等号，若包含则为键值对形式的参数
            if (str_starts_with($param_list[$i], '--') && strpos($param_list[$i], '=') !== false) {
                // 分割参数为键和值
                $p = explode('=', $param_list[$i], 2);
                $config_name = $p[0];
                $config_value = $p[1];
                // 检查配置名是否存在于选项数组中
                if (array_key_exists($config_name, $this->options)) {
                    // 处理--sub-string参数，将其分割为查找和替换的数组
                    if ($config_name == '--sub-string') {
                        $amd = explode('|', $config_value, 2);
                        if (!empty($amd[1])) {
                            $this->sub_string_rules = [
                                explode(',', $amd[0]),
                                explode(',', $amd[1])
                            ];
                        }
                    } else {
                        // 其他参数直接赋值
                        $this->options[$config_name] = $config_value;
                    }
                }
            } else {
                // 处理无等号的参数
                if (in_array($param_list[$i], [
                    '--store-database',
                    '--no-clobber',
                    '--no-verbose',
                    '--mirror',
                    '--recursive',
                    '--page-requisites',
                    '--utf-8',
                    '--span-hosts',
                    '--no-parent',
                    '--adjust-extension',
                    '--restrict-file-names',
                    '--no-check-certificate',
                    '--force-directories'
                ])) {
                    // 这些参数为开关型参数，设置为1表示启用
                    $this->options[$param_list[$i]] = 1;
                } else {
                    // 若不是开关型参数，则作为起始URL
                    $this->options['--start-url'] = $param_list[$i];
                }
            }
        }
        // 若未设置文件保存目录，则默认使用当前脚本所在目录
        if (empty($this->options['--directory-prefix'])) {
            $this->options['--directory-prefix'] = __DIR__;
        }
        $url_parsed = parse_url($this->options['--start-url']);
        $this->start_info += $url_parsed;
        // 起始网址根
        $this->start_info['host'] = strtolower($url_parsed['host']);
        $this->start_info['domain'] = $url_parsed['scheme'] . '://' . $url_parsed['host'] . (empty($url_parsed['port']) ? '' : ':' . $url_parsed['port']) . '/';
        $this->start_info['directory_prefix'] = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $this->options['--directory-prefix']), DIRECTORY_SEPARATOR);
        $this->start_info['host_dir'] = $this->start_info['directory_prefix'] . DIRECTORY_SEPARATOR . $this->start_info['host'] . (isset($url_parsed['port']) ? '_' . $url_parsed['port'] : '');
        // 检查 --load-cookies 和 '--save-cookies' 选项，确保它们指向同一个文件
        if (!empty($this->options['--load-cookies']) && !empty($this->options['--save-cookies'])) {
            if ($this->options['--load-cookies'] != $this->options['--save-cookies']) {
                throw new \InvalidArgumentException('--load-cookies and --save-cookies just specify one.' . PHP_EOL);
            }
        } elseif (empty($this->options['--save-cookies']) && !empty($this->options['--load-cookies'])) {
            $this->options['--save-cookies'] = $this->options['--load-cookies'];
        }

        // 参数严格校验
        if (!is_numeric($this->options['--wait']) || $this->options['--wait'] < 0) {
            throw new \InvalidArgumentException('--wait must be a non-negative number.' . PHP_EOL);
        }
        if (!is_numeric($this->options['--tries']) || $this->options['--tries'] < 1) {
            throw new \InvalidArgumentException('--tries must be a positive integer.' . PHP_EOL);
        }
        if (!is_dir($this->options['--directory-prefix']) && !mkdir($this->options['--directory-prefix'], 0777, true)) {
            throw new \InvalidArgumentException('--directory-prefix is not a valid directory and cannot be created.' . PHP_EOL);
        }

        // 正则表达式参数校验
        $safe_regex = function ($pattern) {
            // 禁止过长、嵌套过深
            if (strlen($pattern) > 256) return false;
            if (substr_count($pattern, '(') > 10) return false;
            // 检查危险修饰符，仅匹配正则结尾的修饰符部分
            if (preg_match('#/(.*?)/([imsuxADSUXJ]*)$#', $pattern, $m)) {
                $modifiers = $m[2] ?? '';
                if (preg_match('/[eSxXAE]/', $modifiers)) return false;
            }
            // 检查正则语法合法性
            return @preg_match('/' . $pattern . '/', '') !== false;
        };
        if (!empty($this->options['--reject-regex']) && !$safe_regex($this->options['--reject-regex'])) {
            throw new \InvalidArgumentException('Unsafe --reject-regex: ' . $this->options['--reject-regex'] . PHP_EOL);
        }
        if (!empty($this->options['--accept-regex']) && !$safe_regex($this->options['--accept-regex'])) {
            throw new \InvalidArgumentException('Unsafe --accept-regex: ' . $this->options['--accept-regex'] . PHP_EOL);
        }
    }
}
// =================== 爬取控制 ===================
class Pget
{
    // 爬虫配置类
    public PgetConfig $config;
    // 配置选项
    public array $cfg = [];
    // 循环计数
    private int $loop_count = 1;
    // 已处理链接表：键为链接，值为布尔值（true=本地文件存在，false=不存在）
    public array $link_table = [];
    // 待处理链接队列
    public SplQueue $pending_queue;
    // 日志文件句柄
    private $log_file_handle = null;
    // 过滤规则
    private array $filter = [];
    // 日志缓存
    private array $log_buffer = [];
    // 扩展名
    private array $extensions = [];
    // 响应头内容类型
    private array $content_type = [];
    // 主机列表
    private array $domains = [];
    // 数据库类型
    private string $db_type = 'mysql';
    // 数据库对象
    private $pdo;
    // 爬虫工具类
    private $funcUtils;
    // 并发爬虫
    private $catcher;

    /**
     * 构造函数，初始化配置和队列
     */
    public function __construct(PgetConfig $config)
    {
        // 保存配置对象
        $this->config = $config;
        // 保存配置选项
        $this->cfg = $this->config->options;
        $this->cfg['start_info'] = $this->config->start_info;
        $this->cfg['sub_string_rules'] = $this->config->sub_string_rules;
        $this->cfg['isWindows'] = $this->config->isWindows;
        $this->cfg['isChineseWindows'] = $this->config->isChineseWindows;
        // 初始化待处理链接队列
        // $this->pending_queue = new SplQueue();
        // 如果配置了输出日志文件，则打开文件句柄并清空旧内容
        if ($this->cfg['--output-file']) {
            $log_file = $this->cfg['start_info']['directory_prefix'] . DIRECTORY_SEPARATOR . $this->cfg['--output-file'];
            $this->log_file_handle = fopen($log_file, 'w');
            if (!$this->log_file_handle) {
                throw new \InvalidArgumentException("Failed to open log file: {$log_file}\n");
            }
        }
        // 可接受的内容类型和对应的扩展名
        $this->content_type = [
            'text/html' => 'html',
            'text/plain' => 'txt',
            'text/css' => 'css',
            'application/javascript' => 'js',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'application/xml' => 'xml',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/x-icon' => 'ico',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
            // ...可扩展
        ];
        $this->extensions = array_values($this->content_type);
        // 若 --span-hosts 为假，则限制在本域名内，相当于 --domains 内只有本域名一条记录。若 --span-hosts 为真，则将 -- domains 格式化为数组，若此时 --domains 为空，相当于只配置了 --span-hosts ，则允许任意域名。
        if ($this->cfg['--span-hosts']) {
            if (empty($this->cfg['--domains'])) {
                $this->domains = [];
            } else {
                $this->domains = array_filter(array_map('strtolower', array_map('trim', explode(',', $this->cfg['--domains']))));
            }
        } else {
            $this->domains = [$this->cfg['start_info']['host']];
        }

        // 过滤配置
        $this->filter['--reject'] = !empty($this->cfg['--reject']) ? explode(',', $this->cfg['--reject']) : null;
        $this->filter['--accept'] = !empty($this->cfg['--accept']) ? explode(',', $this->cfg['--accept']) : null;
        $this->filter['--reject-regex'] = $this->cfg['--reject-regex'];
        $this->filter['--accept-regex'] = $this->cfg['--accept-regex'];
    }
    /**
     * 析构函数，自动调用 close() 方法
     */
    public function __destruct()
    {
        $this->close();
    }
    /**
     * 关闭所有资源
     */
    public function close()
    {
        // 关闭日志文件
        if ($this->log_file_handle && is_resource($this->log_file_handle)) {
            fclose($this->log_file_handle);
            $this->log_file_handle = null;
        }

        // 可以在这里关闭数据库连接、curl 句柄等资源
        if ($this->catcher) {
            unset($this->catcher);
        }

        if ($this->pdo) {
            $this->pdo = null;
        }

        if ($this->funcUtils) {
            $this->funcUtils = null;
        }
    }
    /**
     * 启动主流程
     * 判断参数，选择单URL、批量、递归三种模式
     */
    public function run()
    {
        // 检查是否提供了起始URL或输入文件，若都未提供则终止脚本
        if (!$this->cfg['--start-url'] && !$this->cfg['--input-file']) {
            throw new Exception('Error! No URL');
            return false;
        }

        $url_parsed = parse_url($this->cfg['--start-url']);
        if (!isset($url_parsed['scheme']) || !isset($url_parsed['host'])) {
            throw new Exception('Error! Invalid URL');
            return false;
        }

        // 根据配置选项选择不同的下载模式
        if (!$this->cfg['--mirror'] && !$this->cfg['--recursive'] && !$this->cfg['--input-file']) {
            // 单URL下载模式
            $this->singleRequest($this->cfg['--start-url']);
        } elseif ($this->cfg['--input-file']) {
            // 批量下载模式
            $this->batchRequest($this->cfg['--input-file']);
        } else {
            // 递归下载模式
            $this->recursiveRequest($this->cfg['--start-url']);
        }
    }
    /**
     * 递归爬取
     * 支持断点续传，自动读取数据库和本地目录下已存在的文件，避免重复下载
     * 1. 读取数据库已爬取URL
     * 2. 遍历本地目录下所有文件，将其转换为URL，加入已爬取表
     * 3. 主循环：从队列取出URL，下载并处理，已处理的URL写入数据库
     */
    private function recursiveRequest($url)
    {

        // 创建 pdo 对象
        $db_name = 'catcher_' . preg_replace('/[^a-zA-Z\d_]/', '_', $this->cfg['start_info']['host']);
        if ($this->db_type === 'mysql') {
            $this->pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', 'root');
        } else {
            $this->db_type = 'sqlite';
            $this->pdo = new PDO('sqlite:' . $this->cfg['directory_prefix'] . '/' . $db_name . '.db', '', '');
        }
        $create_db_func_type = "catcher_create_db_{$this->db_type}";

        // 初始化函数工具类
        $this->funcUtils = new FuncCatcher($this->pdo, $this->db_type, $this->cfg, $this->filter, $this->domains);
        // 初始化数据库
        $this->funcUtils->{$create_db_func_type}($db_name);

        // 初始化抓取类
        $this->catcher = new MultiCatcher(
            'concurrency=' . $this->cfg['--max-threads'],
            'tries=' . $this->cfg['--tries'],
            'pause_tries=' . $this->cfg['--pause-tries'],
            'pause_interval=' . $this->cfg['--pause-interval'],
            'pause_time=' . $this->cfg['--pause-time']
        );
        // 绑定回调函数
        $this->catcher->success = function ($url, $result, $chInfo) {
            return $this->success($url, $result, $chInfo);
        };
        $this->catcher->failure = function ($url, $messages) {
            return $this->failure($url, $messages);
        };
        $this->catcher->accesslog = function ($messages) {
            return $this->echo_logs($this->loop_count, $messages);
        };
        $this->catcher->errorlog = function ($messages) {
            return $this->echo_logs($this->loop_count, $messages);
        };
        // 起始链接入队
        if ($this->funcUtils->path_filter_all($this->cfg['--start-url'], $this->filter, $this->domains)) {
            $this->funcUtils->catcher_store_links([$this->cfg['--start-url']]);
        } else {
            throw new Exception('The path is not allowed to be downloaded.');
        }

        if (!$this->cfg['--store-database']) {
            // 若配置了起始URL，则进行冷启动检查
            $this->start_once($url);
        }
        $link_table_tub = [];
        // 开始爬取网页
        while (true) {
            $link_table_tub = $this->funcUtils->catcher_read_links(5000);
            if (count($link_table_tub) === 0 || $this->funcUtils->is_multi_array_empty($link_table_tub)) {
                break;
            }
            $maxThreads = $this->cfg['--max-threads'] ?? 20;

            while (count($link_table_tub) > 0) {
                // 将link_table 按maxThreads分割成数组
                $this->link_table = array_splice($link_table_tub, 0, $maxThreads);

                if (count($this->link_table) === 0 || $this->funcUtils->is_multi_array_empty($this->link_table)) {
                    break;
                }

                $this->pdo->beginTransaction();

                foreach ($this->link_table as $url => $url_info) {
                    $this->catcher->pushJob($url);
                }
                $this->catcher->run();

                $this->pdo->commit();

                // 若设置了操作间隔时间，则进行等待
                if ($this->cfg['--wait']) {
                    $this->echo_logs('Waiting for ' . $this->cfg['--wait'] . ' seconds...');
                    $this->funcUtils->wait($this->cfg['--wait']);
                }
            }
        }
        // 结束网页爬取

    }
    /* 
     * 首次启动检查数据库文件和本地文件
    */
    public function start_once($url)
    {
        // 1.遍历存储目录下所有文件，将文件名转换为URL并加入链接表和队列

        // 待扫描目录
        $host_dir = $this->cfg['start_info']['host_dir'];
        $file_count = 0;

        // 检查存储目录是否存在
        if (is_dir($host_dir)) {
            // 创建递归迭代器，遍历目录下的所有文件
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($host_dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($rii as $file) {
                if ($file->isFile()) {
                    // 获取文件的完整路径
                    $file_path = $file->getPathname();
                    // 转换本地文件路径为相对路径
                    $relative_path = substr($file_path, strlen($host_dir));

                    // 将目录分隔符替换为斜杠
                    $relative_path = str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);
                    // 去除path中的多余的'//'（如有）
                    $relative_path = preg_replace('#(?<!:)//+#', '/', $relative_path);
                    // 生成对应的URL
                    $url = $this->cfg['start_info']['domain'] . ltrim($relative_path, '/');
                    // 链接入队，表示该链接的本地文件已经存在，无需再次下载
                    $this->funcUtils->catcher_store_links([$url], 0);

                    // 输出请求日志信息

                    // 读取内容，处理链接和资源
                    $result = file_get_contents($file_path);
                    // 若设置了镜像或递归下载，则提取并处理页面链接
                    if ($this->cfg['--mirror'] || $this->cfg['--recursive']) {
                        $this->funcUtils->catcher_store_page_links($result, $url);
                    }
                    $this->echo_logs("{$file_path} -> {$url} Founded");
                    $file_count++;
                }
            }
        }

        $this->echo_logs($file_count, 'Links Found In Local Storage.');

        $this->flush_log_buffer();
    }
    /**
     * 根据content-type获取扩展名
     */
    public function get_ext_by_content_type($content_type)
    {
        // 去除content-type中的参数，转换为小写
        $type = strtolower(trim(explode(';', $content_type)[0]));
        // 根据content-type获取对应的扩展名
        return $this->content_type[$type] ?? null;
    }
    // 请求错误处理函数
    public function failure($url, $messages): void
    {
        if ($this->cfg['--mirror'] || $this->cfg['--recursive']) {
            // use_status 超过 2 不需要改为 0，以区别 请求失败 和 已完成处理 的url
            // if ($this->link_table[$url]['use_status'] > 2)
            //     $this->funcUtils->catcher_log_used_status(0, $this->link_table[$url]['id']);
            // else
            $this->funcUtils->catcher_log_used_status('+1', $this->link_table[$url]['id']);
        }

        $this->echo_logs('FORCEECHO', $this->loop_count, date('Y-m-d H:i:s'), $messages);
        $this->loop_count++;
        $this->flush_log_buffer();
    }
    // 请求成功处理函数
    public function success($url, $result, $chInfo)
    {
        $this->echo_logs($this->loop_count, "URL : {$url}\tHttp_Code : {$chInfo['http_code']}");

        $local_file = '';
        $local_file_utf8 = '';

        $this->funcUtils->catcher_log_used_status(0, $this->link_table[$url]['id']);

        if (empty($result)) {
            $this->echo_logs($this->loop_count, $url, 'Response Null');
            return null;
        }

        if ($this->cfg['--utf-8']) $result = $this->funcUtils->mb_encode($result);

        // 获取标题
        if ($this->cfg['--store-database'] && $this->db_type == 'mysql') {
            $html_title = $this->funcUtils->ssub_content($result, '<title>', '</title>');
        }

        if ($this->cfg['--mirror'] || $this->cfg['--recursive']) {
            $this->funcUtils->catcher_store_page_links($result, $url);
        }

        if (!$this->funcUtils->is_multi_array_empty($this->cfg['sub_string_rules'])) {
            $result = $this->funcUtils->sub_content_all($result, $this->cfg['sub_string_rules']);
            // $result = $this->funcUtils->cleanString(
            //     $this->funcUtils->removeAttributesEx(
            //         $this->funcUtils->custom_strip_tags(
            //             $this->funcUtils->removeScriptAndStyle($result),
            //             ['a', 'img', 'br', 'p', 'h2', 'h3']
            //         )
            //     )
            // );
            if (empty($result)) return null;
        }

        if ($this->cfg['--store-database'] && $this->db_type == 'mysql') {
            $this->funcUtils->catcher_store_content($url, $html_title, $result);
        } else {
            $local_file = $this->funcUtils->url_local_path($url, $this->cfg['start_info']['directory_prefix']);
            $local_file_utf8 = $local_file;
            // 兼容中文路径（PHP8+Windows10不需要手动转码路径字符）
            // --adjust-extension: 仅对既不是目录也没有扩展名的URL，根据content-type补全扩展名
            if ($this->cfg['--adjust-extension']) {
                if (empty($chInfo['content_type'])) {
                    $chInfo['content_type'] = 'text/html';
                }
                // 根据响应的content-type获取扩展名
                $content_type = $this->get_ext_by_content_type($chInfo['content_type']);
                // 判断本地文件是否为目录，经过url_local_path处理过，目录结尾是index.html，所以此处不可能是/结尾
                // 若不是目录且没有扩展名，且能获取到扩展名，则补全扩展名
                if (empty($this->funcUtils->get_ext($local_file)) && $content_type) {
                    $url .= '.' . $content_type;
                    $local_file .= '.' . $content_type;
                    $local_file_utf8 .= '.' . $content_type;
                }
            }
            // 获取本地文件所在目录
            $local_dir = dirname($local_file);
            // 若目录不存在，则创建目录
            static $dir_cache = [];
            if (!isset($dir_cache[$local_dir])) {
                if (!is_dir($local_dir) && !mkdir($local_dir, 0777, true)) {
                    throw new \Exception('Failed to create directory: ' . $local_dir);
                    return false;
                }
                $dir_cache[$local_dir] = true;
            }
            if (@file_put_contents($local_file, $result) === false) {
                throw new \Exception('Failed to write file: ' . $local_file);
                // 写入失败则返回false
                return false;
            }
        }
        $this->echo_logs('FORCEECHO', $this->loop_count, date('Y-m-d H:i:s'), "{$url} -> {$local_file_utf8} Saved");
        $this->loop_count++;
        $this->flush_log_buffer();
    }
    /**
     * 日志输出
     * 根据--no-verbose参数控制是否输出
     * 输出日志信息到控制台或文件
     */
    public function echo_logs(...$args)
    {
        // file_put_contents(__DIR__ . '/debug.log', print_r($args, true), FILE_APPEND);
        $stdout = true;
        // 若配置了--no-verbose，则不输出日志信息
        if ($this->cfg['--no-verbose']) {
            $stdout = false;
        }
        $flushlog = false;
        if ($args[0] === 'FORCEECHO') {
            $stdout = true;
            $flushlog = true;
            array_shift($args);
        }
        if ($stdout) {
            // 转换所有参数为字符串，数组用json_encode
            // foreach ($args as &$v) {
            //     if (is_array($v)) {
            //         $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            //     }
            // }
            // unset($v);
            // 构建日志信息
            $log_message = implode("\t", $args) . PHP_EOL;
            // 若配置了输出到文件，则写入缓存
            if ($this->cfg['--output-file']) {
                $this->log_buffer[] = $log_message;
            } else {
                // 否则输出到控制台
                echo $log_message;
            }
        }
        if ($flushlog) {
            $this->flush_log_buffer();
        }
    }
    /**
     * 日志缓存写入文件
     */
    public function flush_log_buffer()
    {
        if (!empty($this->log_buffer) && $this->log_file_handle && is_resource($this->log_file_handle)) {
            fwrite($this->log_file_handle, implode('', $this->log_buffer));
            $this->log_buffer = [];
        }
    }
}
// =================== 工具爬虫 ===================
class FuncUtils
{
    /**
     * 检查多维数组是否为空（所有元素都是空值）
     * 
     * @param mixed $value 要检查的值（数组或标量）
     * @return bool 如果所有元素都是空值返回true，否则false
     */
    public function is_multi_array_empty($value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                // 递归检查数组元素
                if (!$this->is_multi_array_empty($v)) {
                    return false;
                }
            }
            return true;
        }

        // 检查空值（包括0和'0'）
        return $value === '' || $value === null || $value === [] || $value === false;
    }
    /**
     * 过滤：后缀名
     */
    public function path_filter_suffix($url, $filter)
    {
        // 若设置了拒绝的文件后缀，则进行后缀过滤
        if (!empty($filter['--reject'])) {
            foreach ($filter['--reject'] as $i) {
                $i = trim($i);
                // 若URL以拒绝的后缀结尾，则过滤掉该链接
                if ($i !== '' && str_ends_with($url, $i)) {
                    return false;
                }
            }
        }
        // 若设置了接受的文件后缀，则进行后缀过滤
        if (!empty($filter['--accept'])) {
            foreach ($filter['--accept'] as $i) {
                $i = trim($i);
                // 若URL以接受的后缀结尾，则允许该链接
                if ($i !== '' && str_ends_with($url, $i)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
    /**
     * 过滤：正则
     */
    public function path_filter_preg($url, $filter)
    {
        // 参数校验已在配置阶段完成，无需再次校验
        if (!empty($filter["--reject-regex"])) {
            if (preg_match('/' . $filter["--reject-regex"] . '/', $url)) {
                return false;
            }
        }
        if (!empty($filter["--accept-regex"])) {
            if (preg_match('/' . $filter["--accept-regex"] . '/', $url)) {
                return true;
            }
            return false;
        }
        return true;
    }
    /**
     * 检查主机是否允许访问
     * 若 domains 为空，则默认允许所有主机访问
     * @param string $url
     * @return bool
     */
    private function is_host_allowed($url, $domains)
    {
        if (empty($domains)) {
            return true;
        }
        $url_host = parse_url($url, PHP_URL_HOST) ?? '';
        foreach ($domains as $domain) {
            if (strpos($url_host, $domain) !== false) {
                return true;
            }
        }
        return false;
    }
    /**********
     * URL 路径过滤函数：默认接受所有链接，默认拒绝 'javascript:'和'#'
     * 过滤规则支持后缀名过滤
     */
    public function path_filter_all($path, $filter = [], $domain = []): bool
    {
        if (empty($path)) {
            return false;
        }
        /* 需要跳过的链接字符串
     * javascript 和 # 最常见
        'javascript:',
        '#',
        'mailto:',
        'tel:',
        'callto:',
        'skype:',
        'viber:',
        'whatsapp:',
        'weixin:',
        'wechat:'
         */
        // 默认过滤掉包含"javascript:"和"#"的链接
        if (stripos($path, 'javascript:') !== false || stripos($path, '#') !== false || stripos($path, 'data:') !== false) {
            return false;
        }
        if (!$this->is_host_allowed($path, $domain)) {
            return false;
        }
        // 先用后缀名过滤
        if (!$this->path_filter_suffix($path, $filter)) {
            return false;
        }
        // 再用正则过滤
        if (!$this->path_filter_preg($path, $filter)) {
            return false;
        }
        return true;
    }
    /**********
     * 将包含相对路径的URL转换为绝对路径的URL
     * 'http://a.com/b/../../d.e.js' => 'http://a.com/d.e.js'
     * 'http://a.com/../b/c/d/../e/../../f.php' => 'http://a.com/b/f.php'
     */
    public function get_standard_url($url)
    {
        $arr = parse_url($url);
        $path = $arr['path'];
        while (preg_match('/\/[^\/\.]+\/\.\.\//', $path, $match)) {
            $path = str_replace($match, '/', $path);
        }
        while (preg_match('/\/\.{1,2}\//', $path, $match)) {
            $path = str_replace($match, '/', $path);
        }
        return (isset($arr['scheme']) ? $arr['scheme'] . '://' : '') .
            (isset($arr['host']) ? $arr['host'] : '') .
            (isset($arr['port']) ? ':' . $arr['port'] : '') .
            $path .
            (isset($arr['query']) ? '?' . $arr['query'] : '');
    }
    /**********
     * 将当前相对URL转换为完整URL
     * get_absolute_url('./c/d.php','http://w.a.com/a/b');
     */
    public function get_absolute_url($url, $baseurl = '')
    {
        if (empty($url)) return '';
        $srcinfo = parse_url($url);
        if (isset($srcinfo['scheme'])) {
            return $url;
        } else {
            if (!$baseurl) return '';
        }

        $baseinfo = parse_url($baseurl);
        $scheme = $baseinfo['scheme'] ?? '';
        $host = $baseinfo['host'] ?? '';
        $port = isset($baseinfo['port']) ? ":{$baseinfo['port']}" : '';

        if (empty($scheme) || empty($host)) return '';
        if (str_starts_with($url, '//')) return "{$scheme}:{$url}";
        if (str_starts_with($url, '/')) return "{$scheme}://{$host}{$port}{$url}";

        $base_dir_name = (substr($baseurl, -1, 1) == '/') ? $baseurl : dirname($baseurl) . "/";
        return $this->get_standard_url($base_dir_name . $url);
    }
    /**********
     * 检测字符编码并转换成指定编码
     * */
    public function mb_encode($str, $to_encoding = "UTF-8")
    {
        // 优先用header、meta、mb_detect_encoding自动识别
        $encode = null;
        if (preg_match('/<meta.*?charset=["\']?([a-zA-Z0-9\-]+)["\']?/i', $str, $m)) {
            $encode = strtoupper($m[1]);
        }
        if (!$encode && preg_match('/Content-Type:.*?charset=([a-zA-Z0-9\-]+)/i', $str, $m)) {
            $encode = strtoupper($m[1]);
        }
        if (!$encode) {
            $encode = mb_detect_encoding($str, ['UTF-8', 'GB2312', 'GBK', 'BIG5', 'ISO-8859-1', 'ASCII'], true);
        }
        if (!$encode) $encode = 'UTF-8';
        if ($encode != $to_encoding) {
            $str = @mb_convert_encoding($str, $to_encoding, $encode);
        }
        return $str;
    }
    /**********
     * 字符截取方式获得内容
     * 开始字符搜索不到时返回空，结束字符搜索不到时返回空
     * 开始字符不能为空，结束字符为空时返回开始位置到文档尾部字符串
     * @mode : 1 包含前后字符串，0 包含前部不含后部，-1 前后都不包含
     */
    public function sub_content($str, $before, $after, $mode = 1)
    {
        $start = stripos($str, $before);
        if ($start === false)
            return '';
        $str = substr($str, $start);
        if (!$after)
            return $str;
        $length = stripos($str, $after);
        if ($length <= 0)
            return '';

        $start = 0;
        switch ($mode) {
            case -1:
                $start = strlen($before);
                $length = $length - $start;
                break;
            case 0:
                $length = $length - $start;
                break;
            case 1:
                $length = $length + strlen($after);
                break;
        }
        $content = substr($str, $start, $length);

        return $content;
    }
    /**********
     * 字符截取方式获得内容，不要HTML格式 
     */
    public function ssub_content($str, $before, $after, $mode = 1)
    {
        return trim(strip_tags($this->sub_content($str, $before, $after, $mode)));
    }
    /**********
     * 截取网页中符合规则的所有子段落组成新内容
     * @substr_sign 是二维数组
     * 例如：substr_sign[0]['start1','start2'] 和 substr_sign[1]['end1','end2'] 会截取从 start1 到 end1 加 start2 到 end2 的所有字符串
     */
    public function sub_content_all($html, $substr_sign = [])
    {
        if ($this->is_multi_array_empty($substr_sign))
            return $html;
        list($start, $end) = $substr_sign;
        $html_tmp = '';
        for ($i = 0; $i < count($start); $i++) {
            if (!$end[$i]) {
                $end[$i] = '</html>';
            }
            $html_tmp .= $this->sub_content($html, $start[$i], $end[$i], 1);
        }
        return $html_tmp;
    }
    /**
     * 从HTML内容中提取所有链接（A、IMG、JS、CSS等）
     * 
     * 支持两种模式：
     * 1. HTML解析模式：当$from_array为false时，解析HTML字符串提取资源URL
     * 2. 数组处理模式：当$from_array为true时，直接处理传入的URL数组
     * 
     * @param string|array $html_body HTML内容字符串或URL数组
     * @param string $start_uri 起始URL（用于解析相对路径）
     * @param string $current_url 当前URL（优先用于解析相对路径，默认为$start_uri）
     * @param bool $from_array 是否直接处理URL数组（默认false）
     * @return array 提取并格式化后的资源URL数组
     */
    public function get_page_links($html_body, $url, $requisites = false)
    {
        // 若HTML内容为空或起始URI和当前URL都为空，则返回空数组
        if (empty($html_body) || empty($url)) {
            return [];
        }
        $urls = [];
        if (!is_array($html_body)) {
            $unique_links = [];
            // 使用正则表达式提取页面资源链接
            if ($requisites) {
                preg_match_all('/<(?:a|script|link|img)[^>]+(?:src|href|data-original)=[\'"]([^\'"#]+)(?:#[^\'"\/]*)?[\'"][^>]*>/i', $html_body, $matches);
            } else {
                // 只提取a标签，暂无不带引号规则 '/<a[^>]+href=([^>\"\'\s]+?)[> ]/i'
                preg_match_all('/<a[^>]+href=[\'"]([^\'"#]+)(?:#[^\'"\/]*)?[\'"][^>]*>/i', $html_body, $matches);
            }
            // 去除重复的链接
            $unique_links = array_unique($matches[1] ?? []);
        } else {
            // 若传入的是链接数组，则直接去除重复的链接
            $unique_links = array_unique($html_body);
        }
        // 遍历链接数组
        foreach ($unique_links as $path) {
            // 过滤掉以"data:"开头的非正常链接
            if (str_starts_with($path, 'data:')) {
                continue;
            }
            // 格式化URL为绝对地址
            $absolute_url = $this->get_absolute_url($path, $url);
            // 去除URL中的锚点部分
            if (strpos($absolute_url, '#') !== false) {
                $absolute_url = substr($absolute_url, 0, strpos($absolute_url, '#'));
            }
            // 将格式化后的URL加入结果数组
            $urls[] = $absolute_url;
        }
        return $urls;
    }
    /**********
     * URL 转换为本地 PATH
     * 会创建文件的目录
     * @param url
     * @param dir_prefix
     */
    public function url_local_path($url, $dir_prefix): string
    {
        if (empty($url)) {
            return '';
        }
        $url_parsed = is_array($url) ? $url : parse_url($url);
        $path = $url_parsed['path'] ?? '/';

        if (str_ends_with($path, '/')) {
            $path .= 'index.html';
        }
        $decodedPath = rawurldecode($path);
        $query = empty($url_parsed['query']) ? '' : '?' . str_replace('/', '%2F', rawurldecode($url_parsed['query']));
        $file_path = $decodedPath . $query;
        $file_path = ltrim($file_path, '/');
        static $invalidChars = [
            '?' => '%3F',
            '*' => '%2A',
            ':' => '%3A',
            '"' => '%22',
            '<' => '%3C',
            '>' => '%3E',
            '|' => '%7C',
        ];
        $file_path = strtr($file_path, $invalidChars);
        return
            $dir_prefix .
            DIRECTORY_SEPARATOR .
            $url_parsed['host'] .
            (empty($url_parsed['port']) ? '' : '%3A' . $url_parsed['port']) .
            DIRECTORY_SEPARATOR .
            $file_path;
    }
    /**
     * 支持整数秒和小数秒的暂停函数
     * 等待（秒/微秒）
     * @param float $seconds 暂停时间（秒），支持整数或小数
     * @return void
     */
    public function wait($seconds)
    {
        // 若等待时间小于等于0，则返回
        if ($seconds <= 0) {
            return false;
        }
        // 将秒转换为微秒
        $microseconds = (int)($seconds * 1000000);

        // 根据等待时间的类型进行处理
        if (is_int($seconds) || $seconds == (int)$seconds) {
            // 若等待时间为整数，则使用sleep函数等待
            sleep($seconds);
        } else {
            // 否则，使用usleep函数等待
            usleep($microseconds);
        }
    }
    /**********
     * 去除每行头尾部空白
     * 空白包含半角空格、制表符、全角空格
     * 去除连续的空行
     */
    public function cleanString($str)
    {
        // 定义包含全角空格的正则表达式
        $pattern = '/^[\s　]+|[\s　]+$/u';
        $lines = explode("\n", $str);
        $newLines = [];
        foreach ($lines as $line) {
            // 使用正则表达式去除空白字符
            $trimmedLine = preg_replace($pattern, '', $line);
            if (!empty($trimmedLine)) {
                $newLines[] = $trimmedLine;
            }
        }
        return implode("\n", $newLines);
    }
    /**********
     * 去除HTML标签属性，不含 href 、 src 
     */
    public function removeAttributesEx($html)
    {
        // 使用正则表达式匹配 HTML 标签
        return preg_replace_callback('/<(\w+)([^>]*?)>/i', function ($matches) {
            $tag = $matches[1];
            $attributes = $matches[2];
            $newAttributes = '';
            // 分离属性
            if (!empty($attributes)) {
                $attributePairs = preg_split('/\s+/', $attributes);
                foreach ($attributePairs as $attributePair) {
                    if (preg_match('/^(href|src)=["\']?[^"\']+["\']?/i', $attributePair)) {
                        $newAttributes .= $attributePair . ' ';
                    }
                }
            }
            // 确保属性部分和标签名之间有空格
            return '<' . $tag . ($newAttributes ? ' ' . rtrim($newAttributes) : '') . '>';
        }, $html);
    }
    /**********
     * 去除网页标签
     * 支持跳过标签
     */
    public function custom_strip_tags($html, $allowed_tags = array())
    {
        if (empty($allowed_tags)) {
            return preg_replace('/<[^>]*>/', '', $html);
        } else {
            // 将允许的标签数组转换为可用于正则表达式的格式
            $allowed_tags_regex = implode('|', array_map(function ($tag) {
                return preg_quote($tag, '/');
            }, $allowed_tags));
            $pattern = '/<(?![\/]?(' . $allowed_tags_regex . ')\b)[^>]*>/i';
            return preg_replace($pattern, '', $html);
        }
    }
    /**********
     * 去除 HTML 中的 script 和 style 代码 
     * */
    public function removeScriptAndStyle($html)
    {
        // 使用正则表达式移除 <script> 和 <style> 标签及其内容
        $html = preg_replace('#<script.*?>.*?</script>#is', '', $html);
        $html = preg_replace('#<style.*?>.*?</style>#is', '', $html);
        return $html;
    }

    /**********
     * 获取扩展名
     */
    public function get_ext($file)
    {
        if (strpos($file, '?') !== false)
            $file = str_replace(strchr($file, '?'), '', $file);
        if (strpos($file, '#') !== false)
            $file = str_replace(strchr($file, '#'), '', $file);
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $ext;
    }

    // 类结束
}

class FuncCatcher extends Funcutils
{
    private PDO $pdo;
    private string $db_type;
    private array $cfg = [];
    private array $filter = [];
    private array $domains = [];
    public function __construct($pdo, $db_type, $options, $filter, $domains)
    {
        $this->pdo = $pdo;
        $this->db_type = $db_type;
        $this->cfg = $options;
        $this->filter = $filter;
        $this->domains = $domains;
    }

    /**********
     * PDO模式初始化采集数据库结构 logs、sources 和 contents
     * 函数执行前连接数据库时务必设置错误模式和字符模式
     * */
    public function catcher_create_db_mysql($db_name)
    {

        if (empty($this->pdo) || empty($db_name)) return null;

        $tb_logs = 'logs';
        $tb_contents = 'contents';

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('SET NAMES "utf8mb4";');

        $create_table = <<<EOF
		CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
		USE `$db_name`;
		CREATE TABLE IF NOT EXISTS `$tb_logs` (
		`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`use_status` TINYINT(1) NOT NULL DEFAULT '1' ,
		`url` VARCHAR(250) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci' ,
		PRIMARY KEY (`id`),
		INDEX `use_status` (`use_status`),
		UNIQUE INDEX `url` (`url`)
		)
		COLLATE='utf8mb4_general_ci'
		ENGINE=MyISAM ;
		CREATE TABLE IF NOT EXISTS `$tb_contents` (
		`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`url` VARCHAR(250) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci' ,
		`title` VARCHAR(255) NOT NULL DEFAULT '' ,
		`content` LONGTEXT NOT NULL,
		PRIMARY KEY (`id`)
		)
		COLLATE='utf8mb4_general_ci'
		ENGINE=MyISAM
		PARTITION BY HASH(`id`)
		PARTITIONS 64;
		CREATE TABLE IF NOT EXISTS `sources` (
		`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`use_status` TINYINT(1) NOT NULL DEFAULT '1' ,
		`url` VARCHAR(250) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci' ,
		PRIMARY KEY (`id`),
		INDEX `use_status` (`use_status`),
		UNIQUE INDEX `url` (`url`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM ;
		EOF;
        $this->pdo->exec($create_table);
        $this->pdo->exec("USE {$db_name};");
    }
    public function catcher_create_db_sqlite($db_name)
    {
        if (empty($this->pdo) || empty($db_name)) return null;

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $create_table = "CREATE TABLE if NOT EXISTS \"logs\" (\"id\" INTEGER PRIMARY KEY AUTOINCREMENT,\"use_status\" TINYINT NOT NULL,\"url\" VARCHAR(250) UNIQUE NOT NULL);CREATE TABLE if NOT EXISTS \"sources\" (\"id\" INTEGER PRIMARY KEY AUTOINCREMENT,\"use_status\" TINYINT NOT NULL,\"url\" VARCHAR(250) UNIQUE NOT NULL);";
        $this->pdo->exec($create_table);
    }
    /**********
     * 读取数据库内容
     * 没有结果返回false
     */
    public function catcher_read_contents($from = 0, $concur = 0)
    {

        $limit = $concur ?: 1000;
        $sql = "SELECT `id`,`content` FROM `contents` WHERE `id`>" . $from . " ORDER BY `id` ASC LIMIT " . $limit;
        $statement = $this->pdo->prepare($sql);
        if ($statement->execute()) {
            $result_array = [];
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $result_array[$row['id']] = array('content' => $row['content']);
            }
            return $result_array;
        }
        return null;
    }
    /**********
     * 从日志数据表读取链接
     * 没有结果返回false
     */
    public function catcher_read_links($limit = null)
    {
        $sql = "SELECT `id`,`use_status`,`url` FROM logs WHERE `use_status`>0 AND `use_status`<3 LIMIT " . ($limit ?: 10000);
        $statement = $this->pdo->prepare($sql);
        if ($statement->execute()) {
            $result_array = [];
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $result_array[$row['url']] = array('id' => $row['id'], 'use_status' => $row['use_status']);
            }
            return $result_array;
        }
        return null;
    }
    /**********
     * 链接访问次数记录 
     */
    public function catcher_log_used_status($use_status, $id)
    {
        if (!$id) return null;

        $prefix = substr($use_status, 0, 1);
        $use_status = substr($use_status, 1) ?: 0;
        if ($prefix === '+') {
            $sql = " UPDATE logs SET `use_status`=`use_status`+:use WHERE `id`=:id";
        } elseif ($prefix === '-') {
            $sql = "UPDATE logs SET `use_status`=`use_status`-:use WHERE `id`=:id";
        } else {
            $sql = "UPDATE logs SET `use_status`=:use WHERE `id`=:id";
        }
        $statement = $this->pdo->prepare($sql);
        $statement->bindParam(':use', $use_status, \PDO::PARAM_INT);
        $statement->bindParam(':id', $id, \PDO::PARAM_INT);
        if (!$statement->execute()) echo $statement->errorInfo();
    }
    /**********
     * 保存一个链接
     */
    public function catcher_store_link($url, $use = 1)
    {
        if (!$this->path_filter_all($url, $this->filter, $this->domains)) return null;
        $sql['mysql'] = "INSERT IGNORE INTO logs (`use_status`,`url`) VALUES (:use,:url)";
        $sql['sqlite'] = "INSERT OR IGNORE INTO logs (`use_status`,`url`) VALUES (:use,:url)";
        $statement = $this->pdo->prepare($sql[$this->db_type]);
        $statement->bindParam(':use', $use, \PDO::PARAM_INT);
        $statement->bindParam(':url', $url, \PDO::PARAM_STR);
        if (!$statement->execute()) echo $statement->errorInfo();
    }
    /**********
     * 保存链接
     */
    public function catcher_store_links($links, $use = 1)
    {
        if ($use) {
            $action = $this->db_type === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT OR IGNORE INTO';
        } else {
            $action = $this->db_type === 'mysql' ? 'REPLACE INTO' : 'INSERT OR REPLACE INTO';

            $action = 'REPLACE';
        }
        $sql = "{$action} logs (`use_status`,`url`) VALUES (:use,:url)";
        $statement = $this->pdo->prepare($sql);
        foreach ($links as $url) {
            if (!$this->path_filter_all($url, $this->filter, $this->domains)) continue;
            $statement->bindParam(':use', $use, \PDO::PARAM_INT);
            $statement->bindParam(':url', $url, \PDO::PARAM_STR);
            if (!$statement->execute()) echo $statement->errorInfo();
        }
    }
    /**********
     * 保存网页中链接
     */
    public function catcher_store_page_links($html_body, $current_url)
    {
        $links = $this->get_page_links($html_body, $current_url);
        if (empty($links)) return false;
        $this->catcher_store_links($links);
    }
    /**********
     * 保存网页内容
     */
    public function catcher_store_content($url, $html_title, $html_body)
    {
        $sql = 'INSERT IGNORE INTO `contents` (`url`,`title`,`content`) VALUES (:url, :title, :body)';
        $statement = $this->pdo->prepare($sql);
        $statement->bindParam(':title', $html_title, \PDO::PARAM_STR);
        $statement->bindParam(':body', $html_body, \PDO::PARAM_STR);
        $statement->bindParam(':url', $url, \PDO::PARAM_STR);
        if (!$statement->execute()) echo $statement->errorInfo();
    }
    /**********
     * 保存 img,js,css 链接 到数据库
     */
    public function catcher_store_sources($urls)
    {
        if (!empty($urls)) {
            $sql['mysql'] = "INSERT IGNORE INTO `sources` (`use_status`,`url`) VALUES (1,:url)";
            $sql['sqlite'] = "INSERT OR IGNORE INTO `sources` (`use_status`,`url`) VALUES (1,:url)";
            $statement = $this->pdo->prepare($sql[$this->db_type]);
            foreach ($urls as $url) {
                $statement->bindValue(':url', $url, \PDO::PARAM_STR);
                $statement->execute();
            }
        }
    }
    /**********
     * 资源链接下载次数记录
     */
    public function catcher_sources_used_status($use_status, $id)
    {
        if (!$id) return null;

        $prefix = substr($use_status, 0, 1);
        $use_status = substr($use_status, 1) ?: 0;
        if ($prefix == '+')
            $sql = "UPDATE `sources` SET `use_status`=`use_status`+:use WHERE `id`=:id";
        elseif ($prefix == '-')
            $sql = "UPDATE `sources` SET `use_status`=`use_status`-:use WHERE `id`=:id";
        else
            $sql = "UPDATE `sources` SET `use_status`=:use WHERE `id`=:id";
        $statement = $this->pdo->prepare($sql);
        $statement->bindParam(':use', $use_status, \PDO::PARAM_INT);
        $statement->bindParam(':id', $id, \PDO::PARAM_INT);
        if (!$statement->execute()) echo $statement->errorInfo();
    }
}
/**
 * 并发异步浏览器
 */
class MultiCatcher
{
    // 以下是需要配置的运行参数
    // 最大重试次数不需要配置，由主控重新将链接入栈即可
    public $timeout = 5; //默认的超时
    public $proxy = false; //是否使用代理服务器
    public $concurrency = 25; //并发数量
    public $randuseragent = false; //是否自动更换UserAgent
    public $followlocation = false; //是否自动301/302跳转
    public $header = false; // 补充的请求头；可以给每个url单独设置header值
    public $success = ''; // 请求成功回调函数（http发送成功即为请求成功，包括404状态码）
    public $failure = ''; // 请求错误回调函数
    public $errorlog = ''; // 错误日志回调函数
    public $accesslog = ''; // 访问日志回调
    private $jobStack = array(); // 任务栈,可划分优先级,0最高
    private $map;  // 正在采集的句柄集
    private $chs; // 总采集句柄
    private $error_count = 0; // 错误总次数
    public $tries = 0; // 允许的错误次数
    public $pause_tries = 0; // 最多允许暂停次数
    public $pause_interval = 0; // 两次暂停最小间隔（秒）
    public $pause_time = 0; // 每次暂停时长（秒）
    /**
     * 构造函数，参数赋值
     */
    public function __construct(...$args)
    {
        $this->map = new SplObjectStorage();
        foreach ($args as $i) {
            $p = explode('=', $i, 2);
            $public_var = $p[0];
            $publick_val = $p[1];
            if (isset($this->{$public_var}))
                $this->{$public_var} = $publick_val;
        }
    }
    /* 析构函数，关闭文件句柄 */
    public function __destruct()
    {
        if ($this->chs) {
            curl_multi_close($this->chs);
        }
    }

    /**
     * 串行采集
     *
     * @param unknown $url 要采集的地址
     * @param string $referer
     * @param string $proxy
     * @param string $cookie
     * @param string $header
     * @return string html_body
     */
    public function get($url, $cookie = true, $header = false)
    {

        $ch = $this->createHandle($url, $cookie, $header);

        // 开始抓取
        $content = curl_exec($ch);
        $chInfo = curl_getinfo($ch);
        $code = $chInfo['http_code'];
        // 关闭连接
        curl_close($ch);
        $length = strlen($content);
        // 请求出错或反馈内容为空
        if ($code != 200 or $length == 0) {
            call_user_func($this->errorlog, "URL : {$url}\tHttp_Code : {$code}\tLength : {$length}");
            return null;
        }

        // 本次抓取成功
        call_user_func($this->accesslog, "URL : {$url}\tHttp_Code : {$code}\tUsed : " . round($chInfo['total_time'], 2) . " \tLength : {$length}");

        // 返回结果
        return $content;
    }

    /**
     * 添加一个异步任务
     * @param 采集地址 $url
     * @param number $major 优先级 ,0 最高
     * @param string $referer 是否指定了REFERER
     */
    public function pushJob($url, $cookie = true, $header = false)
    {
        $this->jobStack[] = array(
            'url' => $url,
            'cookie' => $cookie,
            'header' => $header
        );
        return $this;
    }

    /**
     * 创建一个抓取句柄
     * @param unknown $url 要抓取的地址
     * @param string $referer
     * @return multitype:resource Ambigous
     */
    private function createHandle($url, $cookie = true, $header = false)
    {
        // 待请求的URL要编码，curl无法处理非ASCII网址
        $url = rawurlencodex($url);
        //构造一个句柄
        $ch = curl_init();

        //构造配置
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_NOBODY => 0,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_RETURNTRANSFER => 1, // 要求返回结果
            CURLOPT_CONNECTTIMEOUT => 5, //连接超时
            CURLOPT_TIMEOUT => $this->timeout, // 超时
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE, // 自动 http 协议
            CURLOPT_FOLLOWLOCATION => $this->followlocation, // 是否自动 301/302跳转
            CURLOPT_USERAGENT => $this->randuseragent ? $this->agents[rand(0, count($this->agents) - 1)] : $this->agents[0], // 是否随机取一个用户AGENT,
        );
        // 设置CURL参数
        curl_setopt_array($ch, $opt);
        // 补充配置
        if (str_starts_with($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_REFERER, $url);

        // 支持多行header，按换行或逗号分割
        $headerArr = [];
        if (!empty($header)) {
            $raw = $header;
            if (is_array($raw)) {
                foreach ($raw as $h) {
                    foreach (preg_split('/[\r\n,]+/', $h) as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $headerArr[] = $line;
                        }
                    }
                }
            } else {
                foreach (preg_split('/[\r\n,]+/', $raw) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $headerArr[] = $line;
                    }
                }
            }
        }
        if ($headerArr) {
            // 设置HTTP请求头
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        if ($cookie) {
            $cookiefile = sys_get_temp_dir() . '/cookie-' . parse_url($url, PHP_URL_HOST) . '.txt';
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        }
        return $ch;
    }

    /**
     * 从待采集任务栈中取任务,加入正在采集的任务集
     */
    private function fillMap()
    {
        // 超过最大重试次数就停止
        static $pause_tries_count = 0;
        static $last_pause_time = 0;

        if ($this->error_count > $this->tries) {
            // 互斥逻辑：如果 pause_tries 和 pause_interval 都配置，则只用 pause_tries
            if ($this->pause_tries > 0) {
                // 按照最大暂停次数逻辑
                if ($pause_tries_count < $this->pause_tries && $this->pause_time > 0) {
                    call_user_func($this->errorlog, "FORCEECHO", "Error limit reached, pausing for {$this->pause_time}s (pause #" . ($pause_tries_count + 1) . ")");
                    sleep($this->pause_time);
                    $pause_tries_count++;
                    $this->error_count = 0; // 归零错误次数
                } else {
                    call_user_func($this->errorlog, "FORCEECHO", "ERROR: Too many retries (pause limit reached)");
                    throw new \Exception("ERROR: Too many retries");
                }
            } elseif ($this->pause_interval > 0 && $this->pause_time > 0) {
                // 按照间隔逻辑
                $now = time();
                if ($now - $last_pause_time > $this->pause_interval) {
                    call_user_func($this->errorlog, "FORCEECHO", "Error limit reached, pausing for {$this->pause_time}s (interval mode)");
                    sleep($this->pause_time);
                    $last_pause_time = $now;
                    $this->error_count = 0; // 归零错误次数
                } else {
                    call_user_func($this->errorlog, "FORCEECHO", "ERROR: Too many retries (interval not reached)");
                    throw new \Exception("ERROR: Too many retries");
                }
            } else {
                // 没有配置暂停参数，直接抛出异常
                call_user_func($this->errorlog, "FORCEECHO", "ERROR: Too many retries (no pause configured)");
                throw new \Exception("ERROR: Too many retries");
            }
        }
        //从待处理列表中取信息到正在处理的列表中
        while (count($this->map) < $this->concurrency) {
            $job = false;
            $job = array_pop($this->jobStack);
            //已经没有待处理的任务
            if (!$job) {
                break;
            }

            $ch = $this->createHandle($job['url'], $job['cookie'], $job['header']);

            //加到总句柄中
            curl_multi_add_handle($this->chs, $ch);
            // 加入正在采集句柄集
            $this->map[$ch] = $job;
        }
        return null;
    }

    /**
     * 处理一个已经采集到的任务
     * @param unknown $done
     */
    private function done($done)
    {
        //子句柄
        $ch = $done['handle'];
        // 获取 cURL 错误码
        $curl_errno = curl_errno($ch);
        // 获取 cURL 错误信息
        $curl_error = curl_error($ch);
        //curl信息
        $chInfo = curl_getinfo($ch);
        //http code
        $code = $chInfo['http_code'] ?? null;
        //原始URL
        $url = $this->map[$ch]['url'];
        //采集到的内容
        $result = curl_multi_getcontent($ch);

        // 请求出错或反馈内容为空
        if ($curl_errno !== 0 || $code === 403) {
            $this->error_count++;
            // 调用 回调方法,对采集的内容进行处理
            call_user_func($this->failure, $url, "URL : {$url}\tError Code : {$curl_errno}\tError Message : {$curl_error}\tHttp Code : {$code}");
        } else {
            // 调用 回调对象的callback方法,对采集的内容进行处理
            call_user_func($this->success, $url, $result, $chInfo);
        }
        //去除此任务
        unset($this->map[$ch]);
    }

    /**
     * 任务入栈后,开始并发采集
     */
    public function run()
    {
        try {
            // 日志函数配置检查
            if (!$this->accesslog) $this->accesslog = function () {};
            if (!$this->errorlog) $this->errorlog = function () {};
            if (!$this->failure) throw new Exception("请设置回调方法failure");
            if (!$this->success) throw new Exception("请设置回调方法success");

            //总句柄
            $this->chs = curl_multi_init();

            //填充任务
            $this->fillMap();

            do { //同时发起网络请求,持续查看运行状态
                do { //如果是正在执行状态,那就继续执行
                    $status = curl_multi_exec($this->chs, $active);
                } while ($status === CURLM_CALL_MULTI_PERFORM);
                // 降低 CPU 占用
                $ready = curl_multi_select($this->chs, 0.5);
                if ($ready === -1) {
                    usleep(10000); // 10ms
                    continue;
                }
                //出现CURLM_OK,终于有请求完成的子任务(可能多个),逐个取出处理
                while (true) {
                    $done = curl_multi_info_read($this->chs);
                    if (!$done) {
                        break;
                    }

                    //对取出的内容进行处理
                    $this->done($done);

                    //去除此任务
                    curl_multi_remove_handle($this->chs, $done['handle']);
                    curl_close($done['handle']);

                    //补充任务
                    $this->fillMap();
                }

                //没有任务了,退出吧
                if (($status !== CURLM_OK or !$active) and !count($this->map)) {
                    break;
                }
            } while (true); //还有句柄处理还在进行中
        } catch (Throwable $e) {
            call_user_func($this->errorlog, "SYSTEM ERROR: " . $e->getMessage());
            curl_multi_close($this->chs);
            throw $e; // 重新抛出异常
        }
    }

    //可以使用的用户代理,随机使用
    private $agents = array(
        'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'
    );
}

/* =============================== 公共函数 ============================= */
/**********
 * 转换网址
 * 所有允许直接出现在 URL 中的保留字符都能被还原
 * 根据 RFC 3986 规范，URL 中可以直接出现的字符包括：
 * 字母：A-Z a-z
 * 数字：0-9
 * - _ . ~
 * 保留字符：: / ? # [ ] @ ! $ & ' ( ) * + , ; =
 * 
 *  */
function rawurlencodex($url)
{
    // 如果只包含URL允许的字符，直接返回
    if (!preg_match('/[^A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]/', $url)) {
        return $url;
    }

    // 优化2：避免不必要的 rawurldecode 操作
    // 直接对URL进行编码
    $encoded = rawurlencode($url);

    // 优化3：使用单次 strtr 替代多次 str_replace
    // 构建静态替换表（避免每次调用重复构建）
    static $replaceMap = [
        '%3A' => ':',
        '%2F' => '/',
        '%3F' => '?',
        '%3D' => '=',
        '%26' => '&',
        '%25' => '%',
        '%2E' => '.',
        '%23' => '#',
        '%40' => '@',
        '%21' => '!',
        '%24' => '$',
        '%27' => "'",   // 单引号
        '%28' => '(',
        '%29' => ')',
        '%2A' => '*',
        '%2B' => '+',
        '%2C' => ',',
        '%3B' => ';',
        '%5B' => '[',   // 左中括号
        '%5D' => ']',   // 右中括号
        '%7E' => '~',
    ];

    return strtr($encoded, $replaceMap);
}
// =================== 错误捕获 ===================
// 致命错误处理
function pget_shutdown_handler()
{
    global $pget;
    $error = error_get_last();
    $usage_mb = round(memory_get_usage() / 1024 / 1024, 2);
    $url = $pget->cfg['--start-url'] ?? '';
    $log_file = __DIR__ . '/pget_shutdown.log';
    $now = date('Y-m-d H:i:s');
    if ($error) {
        $type = $error['type'] ?? 0;
        $type_str = match ($type) {
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR => 'E_USER_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            default => 'OTHER',
        };
        $msg = "[SHUTDOWN] {$now} [致命错误] 类型: {$type_str}\n";
        $msg .= "  消息: {$error['message']}\n";
        $msg .= "  文件: {$error['file']} : {$error['line']}\n";
        $msg .= "  起始URL: {$url}\n";
        $msg .= "  日志文件: {$log_file}\n";
        $msg .= "  内存占用: {$usage_mb} MB\n";
        $msg .= "  说明: 脚本因致命错误终止，请检查上述错误信息。\n";
        echo "\n========== 脚本异常终止 ==========\n";
        echo $msg;
    } else {
        $msg = "[SHUTDOWN] {$now} [正常退出]\n";
        $msg .= "  起始URL: {$url}\n";
        $msg .= "  内存占用: {$usage_mb} MB\n";
        $msg .= "  说明: 脚本正常退出。\n";
        echo "\n========== 脚本正常退出 ==========\n";
        echo $msg;
    }
    file_put_contents($log_file, $msg, FILE_APPEND);
    if (isset($pget) && method_exists($pget, 'flush_log_buffer')) {
        $pget->flush_log_buffer();
    }
}
// 信号处理公共函数
function pget_signal_handler($signal_type, $signal_name, $exit_code)
{
    global $pget;
    $usage_mb = round(memory_get_usage() / 1024 / 1024, 2);
    $now = date('Y-m-d H:i:s');
    $url = $pget->cfg['--start-url'] ?? '';
    $log_file = __DIR__ . '/pget_shutdown.log';
    $msg = "[SIGNAL] {$now} [{$signal_type}: {$signal_name}]\n";
    $msg .= "  起始URL: {$url}\n";
    $msg .= "  日志文件: {$log_file}\n";
    $msg .= "  内存占用: {$usage_mb} MB\n";
    $msg .= "  说明: 收到 {$signal_name} 信号，脚本被" . ($signal_name === 'SIGINT' ? '用户中断' : '外部终止') . "。\n";
    echo "\n========== 脚本被" . ($signal_name === 'SIGINT' ? '用户中断 (Ctrl+C)' : '外部终止 (SIGTERM)') . " ==========\n";
    echo $msg;
    file_put_contents($log_file, $msg, FILE_APPEND);
    if (isset($pget) && method_exists($pget, 'flush_log_buffer')) {
        $pget->flush_log_buffer();
    }
    if (isset($pget->log_file_handle) && is_resource($pget->log_file_handle)) {
        fclose($pget->log_file_handle);
    }
    exit($exit_code);
}
