<?php
// 命令行参数列表，定制初始化配置
$param_list = [
    '', // 控制代替脚本自名
    '--start-url=http://www.ccbbp.com/',
    '--directory-prefix=C:\\workspace\\wwwcrawler',
    '--reject-regex=\?|#|&|(\.rar)|(\.zip)|(\.epub)|(\.txt)|(\.pdf)',
    '--wait=0.5',
    '--recursive',
    '--no-clobber',
    '--page-requisites',
    '--adjust-extension',
    '--no-check-certificate',
    '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    '--tries=20'
];

/**
提示：注意看注释哦！

 * Pget.php
 * 
 * A PHP implementation of Wget, object-oriented refactored version. Only surports HTTP,HTTPS,FTP
 * no cache except link table
 * Version: 2.1 link table shard
 * Author: icos
 * 
 * Usage examples:
 * php pget.php --recursive --wait=0.5 --reject="woff,jpg,png,webp" --accept="html,js,css" --reject-regex="\?" --sub-string="<p id=\"b\">,<p class=\"a b\">|</p>[,</p>]" https://domain/
 * php pget.php https://domain/link
 * php pget.php --input-file="urls.txt"
 * 
 * Pget uses pdo_sqlite to store links for request records.
 * 
 * Parameters:
 *   url                  The request URL, or use --start-url="domain"
 *   --mirror             Copy the whole website. 0 means only download a single URL.
 *   --input-file         Path to a file containing URLs, one per line. All URLs will be downloaded.
 *   --no-clobber         Do not overwrite existing local files; otherwise, overwrite.
 *   --directory-prefix   Directory to store files. Default is a subdirectory named after the hostname.
 *   --reject             Comma-separated list of file suffixes to reject.
 *   --accept             Comma-separated list of file suffixes to accept.
 *   --accept-regex       Regular expression to accept URLs.
 *   --reject-regex       Regular expression to reject URLs.
 *   --sub-string         Cut content. Use "|" to split search and replace, "," to split cells. Use cmd escape for quotes or spaces.
 *   --wait               Seconds (or microseconds) between actions.
 *   --no-verbose         Suppress output messages.
 *   --utf-8              Convert content to UTF-8. Default is on.
 *   --recursive          Download all links in the page. Default is off.
 *   --page-requisites    Download images, CSS, JS for HTML display. Default is off.
 *   --directory-prefix   Save files to this directory. Default is current directory.
 *   --no-parent          Do not ascend to parent directory when recursively retrieving.
 *   --span-hosts         Enable spanning across hosts when recursively retrieving.
 *   --domains            Comma-separated list of domains to follow. Does not enable -H.
 *   --adjust-extension   For HTML files without .html extension, append .html.
 *   --restrict-file-names  Escape non-ASCII and special chars for cross-platform compatibility.
 *   --output-file
 *   --save-cookies，--load-cookies
 *   --force-directories    create a hierarchy of directories.
 *   --no-directories   Do not create a hierarchy of directories when retrieving recursively.
 *   --tries             Number of retries. Default is 20.
 *   --retry-connrefused    Force retry on connection refused. Default is on.
 *   --remote-encoding  Remote encoding. Default is UTF-8.
 *   --local-encoding   Local encoding. Default is UTF-8.
 * 
 * -----------------------------------------------------------------------------
 * 中文说明：
 * 
 * Pget.php
 * 
 * 一个 PHP 版的 Wget。仅下载 HTTP,HTTPS,FTP 文件
 * 链接表分片
 * 版本：2.1
 * 作者：icos
 * 
 * 用法示例：
 * php pget.php --recursive --adjust-extension --restrict-file-names --no-check-certificate --tries=10 --wait=0.5 --save-cookies="cookie" --reject-regex="\?|#|&" --sub-string="<p id=\"b\">,<p class=\"a\">|</p>,</p>" https://domain/
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
 * 
 */

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
if (php_sapi_name() !== 'cli') die("Only be run in CLI mode.\n"); // 检查是否在命令行模式下运行
if (version_compare(PHP_VERSION, '8.0.0', '<')) die("PHP 8.0+ Required.\n"); // PHP版本检查
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
// =================== 启动入口 ===================
// 解析命令行参数，初始化配置和主类，启动主流程

try {
    // 命令行参数正常的话，使用命令行参数
    if (count($argv) > 2) {
        $param_list = $argv;
    }
    // 创建PgetConfig对象，传入命令行参数进行配置初始化
    $config = new PgetConfig($param_list);
    // 创建Pget对象，传入配置对象
    $pget = new Pget($config);
    // 启动主流程
    $pget->run();
} catch (\Throwable $e) {
    // 使用配置中的目录前缀
    $dir_prefix = isset($pget) && isset($pget->cfg['--directory-prefix']) ? $pget->cfg['--directory-prefix'] : __DIR__;
    $log_file = $dir_prefix . '/pget_shutdown.log';
    file_put_contents($log_file, "[EXCEPTION] " . date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    throw $e;
}

// =================== 配置类 ===================
class PgetConfig
{
    // 配置选项
    public array $options = [
        '--mirror' => 0,
        '--recursive' => 0,
        '--input-file' => 0,
        '--no-clobber' => 0,
        '--start-url' => '',
        '--directory-prefix' => '',
        '--reject' => '',
        '--accept' => '',
        '--reject-regex' => '',
        '--accept-regex' => '',
        '--sub-string' => '',
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
    ];
    public array $sub_string_rules = [];
    public bool $isWindows = false;
    public bool $isChineseWindows = false;

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
                        if ($amd[1]) {
                            $this->sub_string_rules = [
                                explode(',', $amd[0]),
                                explode(',', $amd[1])
                            ];
                        }
                    }
                    // 其他参数直接赋值
                    $this->options[$config_name] = $config_value;
                }
            } else {
                // 处理无等号的参数
                if (in_array($param_list[$i], [
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

// =================== 主爬虫类 ===================
class Pget
{
    // 爬虫配置类
    public PgetConfig $config;
    // 配置选项
    public array $cfg = [];
    // 循环计数
    private int $loop_count = 1;
    // 已处理链接表：键为链接，值为布尔值（true=本地文件存在，false=不存在，null=不存在）
    public ArraySharder $link_table;
    // 待处理链接队列
    public SplQueue $pending_queue;
    // 日志文件句柄
    private $log_file_handle = null;
    // 过滤规则
    private array $filter = [];
    // 网络错误次数
    private int $error_count = 1;
    // 上次请求的时间
    private float $last_request_time = 0;
    // 日志缓存
    private array $log_buffer = [];
    // 扩展名
    private array $extensions = [];
    // 响应头内容类型
    private array $content_type = [];
    // 起始链接相关信息
    private array $start_info = [];
    // 主机列表
    private array $domain_list = [];
    // 浏览器句柄
    private $curl_handle = null;
    // 目录前缀
    private string $dir_prefix = '';

    /**
     * 构造函数，初始化配置和队列
     */
    public function __construct(PgetConfig $config)
    {
        // 定义链接表
        $this->link_table = new ArraySharder();
        // 初始化待处理链接队列
        $this->pending_queue = new SplQueue();
        // 保存配置对象
        $this->config = $config;
        // 保存配置选项
        $this->cfg = $this->config->options;
        // 目录前缀
        $this->dir_prefix = str_replace('/', DIRECTORY_SEPARATOR, $this->cfg['--directory-prefix']);
        $this->dir_prefix = rtrim($this->dir_prefix, DIRECTORY_SEPARATOR);
        // 若目录不存在，则创建目录
        if (!is_dir($this->dir_prefix)) {
            mkdir($this->dir_prefix, 0777, true);
        }
        // 如果配置了输出日志文件，则打开文件句柄并清空旧内容
        if ($this->cfg['--output-file']) {
            $log_file = $this->dir_prefix . DIRECTORY_SEPARATOR . $this->cfg['--output-file'];
            $this->log_file_handle = fopen($log_file, 'a');
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
        // 起始链接的解析（用属性作传递，避免重复解析）
        $url_parsed = parse_url($this->cfg['--start-url']);
        if (!isset($url_parsed['scheme']) || !isset($url_parsed['host'])) {
            throw new \InvalidArgumentException("Start URL wrong\n");
        }
        $this->start_info = $url_parsed;
        // 起始链接主机名
        $this->start_info['host'] = strtolower($url_parsed['host']);
        // 生成主机目录名
        $this->start_info['host_dir'] = $this->start_info['host'] . (empty($this->start_info['port']) ? '' : '%3A' . $this->start_info['port']);
        // 起始链接域名
        $this->start_info['domain'] = $url_parsed['scheme'] . '://' . $url_parsed['host'] . (isset($url_parsed['port']) ? ':' . $url_parsed['port'] : '') . '/';
        // 起始链接域名长度
        $this->start_info['strlen'] = strlen($this->start_info['domain']);
        $this->start_info['url'] = $this->cfg['--start-url'];

        // 若--domains有值，格式化为数组
        if (!empty($this->cfg['--domains'])) {
            $this->domain_list = array_filter(array_map('strtolower', array_map('trim', explode(',', $this->cfg['--domains']))));
        }
        // 过滤配置
        $this->filter['--reject'] = !empty($this->cfg['--reject']) ? explode(',', $this->cfg['--reject']) : null;
        $this->filter['--accept'] = !empty($this->cfg['--accept']) ? explode(',', $this->cfg['--accept']) : null;
        $this->filter['--reject-regex'] = $this->cfg['--reject-regex'];
        $this->filter['--accept-regex'] = $this->cfg['--accept-regex'];
    }

    /**
     * 析构函数，关闭curl句柄和数据库连接
     */
    public function __destruct()
    {
        unset($this->pending_queue);
        unset($this->link_table);
        // 若curl句柄存在，则关闭curl句柄
        if ($this->curl_handle) {
            curl_close($this->curl_handle);
            $this->curl_handle = null;
        }
        // 若日志文件句柄存在，则关闭并释放
        if ($this->log_file_handle && is_resource($this->log_file_handle)) {
            // 最后写入剩余日志缓存，万一有错误也忽略
            @fwrite($this->log_file_handle, implode('', $this->log_buffer));
            fclose($this->log_file_handle);
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
            $this->echo_logs('FORCEECHO', 'Error! No URL');
            return false;
        }
        // 起始URL格式错误就终止脚本
        if (!isset($this->start_info['scheme']) || !isset($this->start_info['host'])) {
            $this->echo_logs('FORCEECHO', 'Error! Invalid URL');
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
        // 输出请求完成信息
        $this->echo_logs($this->cfg['--start-url'], $this->link_table->count(), 'Gettings Finished At', date('Y-m-d H:i:s'));
    }

    /**
     * 单个URL请求
     * 只下载一个URL
     */
    private function singleRequest($url)
    {
        try {
            // 调用下载并保存内容的方法
            $this->catcher_url_to_local($url);
        } catch (\Throwable $e) {
            $this->echo_logs($this->loop_count, $url, 'Exception: ' . $e->getMessage());
        }
        $this->flush_log_buffer();
    }

    /**
     * 批量请求
     * 从文件读取URL列表，逐个下载
     */
    private function batchRequest($filename)
    {
        $this->echo_logs('Request URLs from file: ', $filename);

        $handle = fopen($filename, 'r');
        if ($handle) {
            while (($url = fgets($handle)) !== false) {
                $url = trim($url);
                if ($url === '') continue;
                try {
                    $this->catcher_url_to_local($url);
                } catch (\Throwable $e) {
                    $this->echo_logs($this->loop_count, $url, 'Exception: ' . $e->getMessage());
                }
                $this->loop_count++;
                $this->flush_log_buffer();
                // 检查并处理信号
                if (function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();
            }
            fclose($handle);
        } else {
            $this->echo_logs($this->loop_count, 'Failed to open input file: ' . $filename);
        }

        $this->flush_log_buffer();
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

        // 尝试从数据库加载已有数据
        if (!$this->loadFromDatabase()) {
            $this->echo_logs('Failed to load data from database.');
            // 进行冷启动检查，读取本地文件
            $this->start_once();
        }
        // 入队起始链接
        if ($this->path_filter_all($url)) {
            $this->add_enqueue_if_new($url);
        } else {
            return false;
        }
        // 主循环
        while ($this->pending_queue->isEmpty() === false) {
            // 从队列中取出一个URL
            $url = $this->pending_queue->dequeue();

            try {
                // 调用下载并保存内容的方法
                $this->catcher_url_to_local($url);
            } catch (\Throwable $e) {
                $this->echo_logs($this->loop_count, $url, 'Exception: ' . $e->getMessage());
            }
            $this->loop_count++;
            // 每一轮操作后写入日志
            $this->flush_log_buffer();
            // 检查并处理信号
            if (function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();
        }
        // 最后一次日志缓存刷新
        $this->flush_log_buffer();
    }

    /* 
     * 首次启动检查数据库文件和本地文件
    */
    public function start_once()
    {
        // 1.遍历存储目录下所有文件，将文件名转换为URL并加入链接表和队列

        // 生成存储目录路径
        $storage_dir =  $this->dir_prefix . DIRECTORY_SEPARATOR . $this->start_info['host_dir'];

        // 检查存储目录是否存在
        if (is_dir($storage_dir)) {
            // 创建递归迭代器，遍历目录下的所有文件
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($storage_dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($rii as $file) {
                if ($file->isFile()) {
                    // 获取文件的完整路径
                    $file_path = $file->getPathname();

                    // 转换本地文件路径为相对路径
                    $relative_path = substr($file_path, strlen($storage_dir));
                    // 将目录分隔符替换为斜杠
                    $relative_path = str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);
                    // 去除path中的多余的'//'（如有）
                    $relative_path = preg_replace('#(?<!:)//+#', '/', $relative_path);
                    // gb2312编码转换为utf-8
                    if ($this->config->isChineseWindows && !mb_detect_encoding($relative_path, 'UTF-8')) {
                        $relative_path = mb_convert_encoding($relative_path, 'UTF-8', 'GB2312');
                    }
                    // 生成对应的完整URL
                    $url = $this->start_info['domain'] . ltrim($relative_path, '/');
                    // 标记本地文件对应的URL为true（文件已存在），这个URL不一定是真实的网址，仅用来帮助文件中的相对路径链接
                    $this->link_table_add_item($url, true);
                    // 去除常见的文件夹索引文件名index,default等
                    $url = preg_replace('/(?:index|default)(?:\.[a-zA-Z0-9]+)?$/', '', $url);
                    // 如果是自动补全扩展名的模式，则这里把扩展名去除 （暂时去除HTML文件）
                    if ($this->cfg['--adjust-extension']) {
                        $url = preg_replace('/\.html$/', '', $url);
                    }
                    // 调整后的链接也标记true，避免重复下载
                    $this->link_table_add_item($url, true);


                    // 读取文件内容，处理链接和资源
                    $response = file_get_contents($file_path);
                    // 提取页面链接
                    $links = $this->get_page_links($response, $url);
                    // 处理页面链接，加入队列并加入链接表（导致内存异常地大）
                    $this->add_page_links($links);
                    // 只加入链接表
                    $this->add_linktable_if_new($links);
                    // 输出请求日志信息
                    $this->echo_logs('FORCEECHO', "{$file_path} -> {$url}",  'Found, Links Add');
                }
            }
        }
        // 刷新日志缓存
        $this->flush_log_buffer();
        // 链接表转入队列
        $this->add_linktable_to_queue();
        // 刷新缓存到数据库
        $this->saveLinksToDatabase();
        // 释放资源
        unset($rii);
        unset($file);
        $this->echo_logs($this->link_table->count(), 'Files Found.' . PHP_EOL);
    }
    /**
     * 下载并保存内容，处理页面资源和链接
     * 1. 判断是否需要下载
     * 2. 下载内容，自动补全扩展名
     * 3. 处理依赖资源和递归链接
     * 4. 保存到本地
     * 5. 记录到临时表
     * 6. 为避免频繁的parse_url，故将解析后的数组作为参数传递
     */
    public function catcher_url_to_local($url)
    {
        // 检查URL是否需要过滤
        if (!$this->path_filter_all($url)) {
            // 若URL被过滤，则输出过滤日志信息并返回
            $this->echo_logs($this->loop_count, $url, 'Reject');
            return false;
        }

        if ($this->error_count > $this->cfg['--tries']) {
            // 如果连续错误超过--tries次，则退出循环
            $this->echo_logs("{$this->error_count} error tries, exit");
            throw new \RuntimeException("{$this->error_count} error tries, exit");
        }

        // 输出请求日志信息
        $this->echo_logs($this->loop_count, $url, date('Y-m-d H:i:s'), 'Getting');

        // 获取内容截取的起止标记
        $sub_string_rules = $this->config->sub_string_rules;

        // 初始化结果和本地文件路径
        $response = '';
        $local_file = '';
        $is_file_exist = false;
        $url_parsed = parse_url($url);

        // 生成本地保存路径
        $local_file = $this->url_local_path($url, $this->dir_prefix);

        // 兼容中文路径（PHP8 + Windows 10 19044 不需要手动对路径转码）
        // if ($this->config->isChineseWindows && !mb_detect_encoding($local_file, 'GB2312')) {$local_file = mb_convert_encoding($local_file, 'GB2312');}
        // 本地文件名不存在时跳过
        if (empty($local_file)) return false;

        // 检查本地文件是否存在，使用链接表代替实时 file_exists 大批量时影响性能
        $is_file_exist = ($is_file_exist || $this->link_table_get_item($url)) ? true : false;

        // 目录链接处理
        if (str_ends_with($url, '/')) {
            $url2 = $url . 'index.html';
            $is_file_exist = ($is_file_exist || $this->link_table_get_item($url2)) ? true : false;
        }

        // 自动扩展名的情况处理，假设存在html扩展名
        if ($this->cfg['--adjust-extension']) {
            $url3 = $url . '.html';
            $is_file_exist = ($is_file_exist || $this->link_table_get_item($url3)) ? true : false;
        }

        // 判断是否需要重新下载
        if ($is_file_exist && $this->cfg['--no-clobber']) {
            $this->echo_logs('FORCEECHO', $this->loop_count, date('Y-m-d H:i:s'), "{$url} -> {$local_file}", 'Exists, Skip');
        } else {
            // 若需要下载，则输出主机IP信息
            static $host_ip = [];
            if (isset($host_ip[$url_parsed['host']])) {
                $ip = $host_ip[$url_parsed['host']];
            } else {
                $ip = gethostbyname($url_parsed['host']);
                $host_ip[$url_parsed['host']] = $ip;
            }
            $this->echo_logs($this->loop_count, "{$url_parsed['host']} -> {$ip}");
            // 发起网络请求
            list($response, $http_info) = $this->browser($url);
            // 若响应内容为空，则输出日志信息并返回
            if ($response === false) {
                $this->link_table_add_item($url, false); // 明确标记失败状态
                $this->echo_logs($this->loop_count, $url, 'Response Null');
                return false;
            }
            // --adjust-extension: 仅对既不是目录也没有扩展名的URL，根据content-type补全扩展名
            if ($this->cfg['--adjust-extension']) {
                if (empty($http_info['content_type'])) {
                    $http_info['content_type'] = 'text/html';
                }
                // 根据响应的content-type获取扩展名
                $content_type = $this->get_ext_by_content_type($http_info['content_type']);
                // 判断本地文件是否为目录，经过url_local_path处理过，目录结尾是index.html，所以此处不可能是/结尾
                // 判断本地文件名是否有扩展名
                $has_ext = false;
                foreach ($this->extensions as $i) {
                    if (str_ends_with($local_file, $i)) {
                        $has_ext = true;
                        break;
                    }
                }
                // 若不是目录且没有扩展名，且能获取到扩展名，则补全扩展名
                if (!$has_ext && $content_type) {
                    $url4 = $url . '.' . $content_type;
                    $local_file .= '.' . $content_type;
                    // 再次检查检查本地文件是否存在
                    $is_file_exist = ($is_file_exist || $this->link_table_get_item($url4)) ? true : false;
                }
            }

            // 若设置了转换为UTF-8编码，则进行编码转换
            if ($this->cfg['--utf-8']) {
                $response = $this->mb_encode($response);
            }
            // 若设置了镜像或递归下载，则提取并处理页面链接
            if ($this->cfg['--mirror'] || $this->cfg['--recursive']) {
                $links = $this->get_page_links($response, $url);
                $this->add_page_links($links);
                $this->echo_logs($this->loop_count, $url,  'Links Add');
            }
            // 若设置了内容截取，则进行内容截取
            if (!empty($this->cfg['--sub-string'])) {
                $response = $this->sub_content_all($response, $sub_string_rules);
                $this->echo_logs($this->loop_count, $url,  'Response Cut');
            }
            // 若截取后的内容为空，则返回
            if (empty($response)) {
                return false;
            }


            // 否则，保存文件
            try {

                // 获取本地文件所在目录并创建
                $this->createDirectoryForFile($local_file, $this->loop_count);

                // 判断是否需要保存文件。上方有 --adjust-extension 添加扩展名导致 is_file_exist 被修正的情况，故需要再次判断
                if ($is_file_exist && $this->cfg['--no-clobber']) {
                    $this->echo_logs($this->loop_count, date('Y-m-d H:i:s'), "{$url} -> {$local_file}", 'Unwrite');
                } elseif (@file_put_contents($local_file, $response) === false) {
                    $this->echo_logs('FORCEECHO', $this->loop_count, date('Y-m-d H:i:s'), $url, 'Failed to write file: ' . $local_file);
                    // 写入失败则返回false
                    return false;
                }
                // 到这步表明文件已存在，则将链接表中记录值改为 true 
                // 在链接表中设置true
                $this->link_table_add_item($url, true);
                // 原URL也设置为true
                if (isset($url2)) $this->link_table_add_item($url2, true);
                if (isset($url3)) $this->link_table_add_item($url3, true);
                if (isset($url4)) $this->link_table_add_item($url4, true);

                $this->echo_logs('FORCEECHO', $this->loop_count, date('Y-m-d H:i:s'), "{$url} -> {$local_file}", 'Saved');
            } catch (\Throwable $e) {
                $this->echo_logs($this->loop_count, $url, 'File Exception: ' . $e->getMessage());
                return false;
            }
        }

        // 若设置了操作间隔时间，则进行等待
        if ($this->cfg['--wait']) {
            $this->echo_logs('Waiting ' . $this->cfg['--wait'] . ' seconds');
            $this->wait($this->cfg['--wait']);
        }

        return true;
    }
    /**
     * 创建目录并处理可能的文件冲突
     * @param string $local_file 本地文件路径
     * @param int $ taskId 任务ID（用于日志输出）
     * @return bool 目录创建成功返回true，失败抛出异常
     * @throws Exception 当目录创建失败时抛出异常
     */
    private function createDirectoryForFile(string $local_file, int $taskId): bool
    {
        // 获取本地文件所在目录
        $local_dir = dirname($local_file);

        // 若目录不存在，则创建目录
        static $dir_cache = [];
        if (!isset($dir_cache[$local_dir])) {
            if (!is_dir($local_dir)) {
                // 清除之前的错误信息
                error_clear_last();

                if (!mkdir($local_dir, 0777, true)) {
                    $error = error_get_last();
                    $errorMessage = $error['message'] ?? 'Unknown error';

                    // 首先使用链接表检查本地文件是否存在，如果没有查到，再使用is_file等函数判断
                    $fileExists = false;

                    // 尝试通过本地路径反向构造URL来查询链接表
                    $relativePath = str_replace(
                        $this->dir_prefix . DIRECTORY_SEPARATOR . $this->start_info['host_dir'] . DIRECTORY_SEPARATOR,
                        '',
                        $local_dir
                    );

                    // 构造可能的URL进行检查
                    $possibleUrl = $this->start_info['domain'] . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                    $possibleUrl = rtrim($possibleUrl, '/'); // 确保URL格式正确

                    // 使用链接表检查
                    if ($this->link_table_get_item($possibleUrl) === true) {
                        $fileExists = true;
                    }
                    // 如果链接表中没有，再检查实际文件系统
                    else if (file_exists($local_dir) && is_file($local_dir)) {
                        $fileExists = true;
                    }

                    // 如果存在同名文件，则尝试重命名为"目录名.html"
                    if ($fileExists) {
                        $renamedFile = $local_dir . '.html';
                        if (rename($local_dir, $renamedFile)) {
                            // 如果是通过链接表发现的冲突，更新链接表
                            if ($this->link_table_get_item($possibleUrl) !== true) {
                                $this->link_table_add_item($possibleUrl, true); // 原URL该为true表示存在，后续链接无需下载
                                $this->link_table_add_item($possibleUrl . '.html', true); // 新URL对应文件
                            }

                            $this->echo_logs('FORCEECHO', $taskId, 'Renamed file "' . $local_dir . '" to "' . $renamedFile . '" to make way for directory creation');

                            // 再次尝试创建目录
                            error_clear_last();
                            if (!mkdir($local_dir, 0777, true)) {
                                $secondError = error_get_last();
                                $secondErrorMessage = $secondError['message'] ?? 'Unknown error';

                                // 如果还是失败，则输出原因并抛出异常
                                $this->echo_logs('FORCEECHO', $taskId, 'Failed to create directory after renaming conflicting file: ' . $local_dir . ' - Error: ' . $secondErrorMessage);
                                throw new \Exception('Failed to create directory after renaming conflicting file: ' . $local_dir . ' - Error: ' . $secondErrorMessage);
                            }
                        } else {
                            // 重命名失败
                            $this->echo_logs('FORCEECHO', $taskId, 'Failed to rename conflicting file "' . $local_dir . '" to "' . $renamedFile . '"');
                            throw new \Exception('Failed to create directory: ' . $local_dir . ' - Error: ' . $errorMessage . ' and failed to rename conflicting file');
                        }
                    } else {
                        // 不是因为同名文件存在导致的失败，根据错误信息判断具体原因
                        if (strpos($errorMessage, 'Permission denied') !== false || strpos($errorMessage, 'errno 13') !== false) {
                            $this->echo_logs('FORCEECHO', $taskId, 'Failed to create directory (Permission denied): ' . $local_dir);
                        } elseif (strpos($errorMessage, 'No space left on device') !== false || strpos($errorMessage, 'errno 28') !== false) {
                            $this->echo_logs('FORCEECHO', $taskId, 'Failed to create directory (No space left): ' . $local_dir);
                        } else {
                            $this->echo_logs('FORCEECHO', $taskId, 'Failed to create directory: ' . $local_dir . ' - Error: ' . $errorMessage);
                        }

                        // 抛出异常
                        throw new \Exception('Failed to create directory: ' . $local_dir . ' - Error: ' . $errorMessage);
                    }
                }
            }
            $dir_cache[$local_dir] = true;
        }

        return true;
    }
    /**
     * 链接入队（去重）
     * 将新发现的链接加入待处理队列
     */
    public function add_page_links($links)
    {
        // 若链接数组为空，则返回
        if (empty($links)) {
            return false;
        }
        // 遍历链接数组
        foreach ($links as $url) {
            // 跳过不允许的链接
            if (!$this->path_filter_all($url)) {
                continue;
            }
            // 若链接未处理过，且是允许的链接，则加入队列
            $this->add_enqueue_if_new($url);
        }
    }

    // 从链接表取出未处理链接
    public function add_linktable_to_queue(): void
    {
        // 将链接表中的false值（文件不存在）加入队列，等待下载
        foreach ($this->link_table->shards as $shard) {
            foreach ($shard as $url => $use) {
                if ($use === true) {
                    continue;
                }
                $this->add_enqueue_if_new($url);
            }
        }
    }
    // 新链接入链接表
    private function add_linktable_if_new($links)
    {
        foreach ($links as $url) {
            // 跳过不允许的链接
            if (!$this->path_filter_all($url)) {
                continue;
            }
            // 如果链接以存在本地文件则不更新，否则添加到链接表：false（文件不存在）
            if (empty($this->link_table_get_item($url))) {
                $this->link_table_add_item($url, false);
            }
        }
    }
    // 新链接入队列
    private function add_enqueue_if_new($url)
    {
        if ($this->link_table->getItem($url) === null) {
            $this->pending_queue->enqueue($url);
            $this->saveLinksToDatabase($url); // 保存到日志
        }
    }
    // 统一处理链接表入表
    private function link_table_add_item($url, bool $value)
    {
        $this->link_table->addItem($url, $value);
        $this->saveLinksToDatabase($url, (int)$value); // 保存到日志
    }
    // 统一处理链接表取值
    private function link_table_get_item($url)
    {
        return $this->link_table->getItem($url);
    }
    /**
     * 日志输出
     * 根据--no-verbose参数控制是否输出
     * 输出日志信息到控制台或文件
     */
    public function echo_logs(...$args)
    {
        $stdout = true;
        // 若配置了--no-verbose，则不输出日志信息
        if ($this->cfg['--no-verbose']) {
            $stdout = false;
        }
        if ($args[0] === 'FORCEECHO') {
            $stdout = true;
            array_shift($args);
        }
        if ($stdout) {
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

    /**
     * 过滤：总入口
     * 1. 默认只允许当前主域名下链接（除非--span-hosts）
     * 2. 后缀过滤
     * 3. 正则过滤
     */
    public function path_filter_all($url)
    {
        if (empty($url)) {
            return false;
        }
        if (stripos($url, 'javascript:') !== false || stripos($url, '#') !== false || stripos($url, 'data:') !== false) {
            return false;
        }
        if (!$this->is_host_allowed($url)) {
            return false;
        }
        if (!$this->path_filter_suffix($url)) {
            return false;
        }
        if (!$this->path_filter_preg($url)) {
            return false;
        }
        return true;
    }
    /**
     * 检查主机是否允许访问（--span-hosts + --domains 联合作用）
     * @param string $url
     * @return bool
     */
    private function is_host_allowed($url)
    {
        if (is_array($url)) {
            $url_parsed = $url;
        } else {
            $url_parsed = parse_url($url);
        }
        $url_host = strtolower($url_parsed['host']);
        // 默认只允许当前主域名下的链接，除非--span-hosts为真
        if (empty($this->cfg['--span-hosts'])) {
            if ($url_host !== $this->start_info['host']) {
                return false;
            }
        } else {
            // --span-hosts为真时，才启用--domains白名单过滤。只要 domains 数组中任意一个元素被包含在当前 URL 的主机名中（字符串包含关系），就允许访问
            if (!empty($this->cfg['--domains'])) {
                $allowed = false;
                foreach ($this->domain_list as $domain) {
                    if (strpos($url_host, strtolower($domain)) !== false) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 过滤：后缀名
     */
    public function path_filter_suffix($url)
    {
        // 获取过滤规则
        $filter = $this->filter;
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
    public function path_filter_preg($url)
    {
        // 获取过滤规则
        $filter = $this->filter;
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
     * 提取页面链接（a、img、js、css等）
     * 支持正则批量提取
     * @param url 当前正在访问的URL
     */
    public function get_page_links($html_body, $url, $from_array = false)
    {
        // 若HTML内容为空或起始URI和当前URL都为空，则返回空数组
        if (empty($html_body) || empty($url)) {
            return [];
        }
        $urls = [];
        if (!$from_array) {
            $unique_links = [];
            // 使用正则表达式提取页面资源链接
            if (!empty($this->cfg['--page-requisites'])) {
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

    /**
     * 生成本地保存路径
     * @param url 传入链接
     * 目录名包含端口号，兼容Win/Linux，支持特殊字符转义
     */
    public function url_local_path($url, $dir_prefix): string
    {
        if (empty($url)) return '';

        // 移除URL中的井号部分
        $url = strtok($url, '#');
        // 解码，以防重复编码
        $url = rawurldecode($url);

        $url_parsed = parse_url($url);

        $host = $url_parsed['host'] ?? '';
        $path = $url_parsed['path'] ?? '/';
        $query = empty($url_parsed['query']) ? '' : '?' . $url_parsed['query'];

        // 限制目录深度
        if ($this->cfg['--level']) {
            $trimmed = trim($path, '/');
            if ($trimmed !== '') {
                $depth = substr_count($trimmed, '/');
                if ($depth > $this->cfg['--level']) {
                    return '';
                }
            }
        }

        if (str_ends_with($path, '/')) {
            $path .= 'index.html';
        }

        if ($this->cfg['--restrict-file-names']) { // 编码路径

            // 使用不会被rawurlencode编码的安全字符串作为临时分隔符
            $tempDirSep = '__DIR_SEP__';  // 目录分隔符临时标记
            $tempDotSep = '__DOT_SEP__';  // 扩展名分隔符临时标记

            $pathinfo = pathinfo($path);
            $filename = empty($pathinfo['filename']) ? '' : $tempDirSep . $pathinfo['filename'];
            $ext = empty($pathinfo['extension']) ? '' : $tempDotSep . $pathinfo['extension'];
            $dirname = empty($pathinfo['dirname']) || ($pathinfo['dirname'] === '\\') ? '' : str_replace('/', $tempDirSep, $pathinfo['dirname']);

            $file_path = $dirname . $filename . $ext . $query;

            $file_path = rawurlencode($file_path);

            // 还原临时分隔符
            $file_path = str_replace([$tempDirSep, $tempDotSep], ['/', '.'], $file_path);
        } else { // 保持原路径
            $file_path = $path . $query;
        }
        $file_path = ltrim($file_path, '/');

        // Windows和Linux系统不允许出现在本地路径中的字符
        $invalidChars = [
            '?' => '%3F',
            '*' => '%2A',
            ':' => '%3A',
            '"' => '%22',
            '<' => '%3C',
            '>' => '%3E',
            '|' => '%7C',
        ];
        if ($this->config->isWindows) {
            $invalidChars['/'] = DIRECTORY_SEPARATOR;
        }
        $file_path = strtr($file_path, $invalidChars);

        if (DIRECTORY_SEPARATOR !== '/') {
            $file_path = str_replace('/', DIRECTORY_SEPARATOR, $file_path);
        }

        return $dir_prefix . DIRECTORY_SEPARATOR . $host .
            (empty($url_parsed['port']) ? '' : '%3A' . $url_parsed['port']) .
            DIRECTORY_SEPARATOR . $file_path;
    }

    /**
     * 多段内容截取
     * 支持多组起止标记批量截取
     */
    public function sub_content_all($html, $sub_string_rules = [])
    {
        // 若起止标记数组为空，则返回原始HTML内容
        if (is_multi_array_empty($sub_string_rules)) {
            return $html;
        }
        // 解包起止标记数组
        list($start, $end) = $sub_string_rules;
        $html_tmp = '';
        // 遍历起止标记数组
        for ($i = 0; $i < count($start); $i++) {
            // 若结束标记为空，则默认为</html>
            if (!$end[$i]) {
                $end[$i] = '</html>';
            }
            // 调用单段内容截取方法，拼接截取结果
            $html_tmp .= $this->sub_content($html, $start[$i], $end[$i], 1);
        }
        return $html_tmp;
    }

    /**
     * 单段内容截取
     */
    public function sub_content($str, $before, $after, $mode = 1)
    {
        // 查找起始标记的位置
        $start = stripos($str, $before);
        if ($start === false) {
            return '';
        }
        // 从起始标记位置开始截取字符串
        $str = substr($str, $start);
        if (!$after) {
            return $str;
        }
        // 查找结束标记的位置
        $length = stripos($str, $after);
        if ($length <= 0) {
            return '';
        }
        $start = 0;
        // 根据截取模式调整起始位置和截取长度
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
        // 截取内容
        $content = substr($str, $start, $length);
        return $content;
    }

    /**
     * 网络请求，支持GET/POST/HEAD
     * 支持自定义User-Agent、Header、Cookie、SSL等
     */
    public function browser($url, $method = 'GET', $postfields = [])
    {
        $url = rawurlencodex($url);
        // 若URL为空，则返回false和空数组
        if (empty($url)) {
            return [false, []];
        }
        // 检查cURL扩展是否启用
        if (!function_exists('curl_init')) {
            throw new \InvalidArgumentException('cURL extension is not enabled. Please enable cURL extension in your PHP configuration.');
        }
        $response = '';
        $http_info = [];
        // 只初始化一次
        if ($this->curl_handle === null) {
            $this->curl_handle = curl_init();
        }
        $ch = $this->curl_handle;
        // 设置URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // 设置HTTP版本为1.1
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
        // 设置返回响应内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 设置跟随重定向
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // 设置超时时间为10秒
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        // 设置连接超时时间为10秒
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        // 设置支持gzip和deflate压缩
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        // user-agent处理
        $ua = $this->cfg['--user-agent'];
        if (empty($ua)) {
            $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36";
        }
        // 设置User-Agent
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if ($scheme === 'https') {
            // 根据配置选项决定是否验证SSL证书
            if ($this->cfg['--no-check-certificate']) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            }
        }
        if ($method === 'HEAD') {
            // 若为HEAD请求，只获取响应头
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
        } else {
            // 其他请求获取响应内容
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }
        // 判断是否POST
        if (!empty($postfields)) {
            // 若有POST数据，则设置为POST请求
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        } else {
            // 否则为GET请求
            curl_setopt($ch, CURLOPT_POST, false);
        }
        // 设置Referer为当前URL
        curl_setopt($ch, CURLOPT_REFERER, $url);
        // cookie文件处理，仅当--save-cookies或者--load-cookies有值时启用cookie，因为程序把读取和写入cookie放在同一个文件，不要配置成不同的文件名
        if (!empty($this->cfg['--save-cookies'])) {
            // 生成cookie文件路径
            $cookiejar = $this->dir_prefix . DIRECTORY_SEPARATOR . $this->cfg['--save-cookies'];
            // 设置cookie文件
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
        }
        // 处理--header参数
        $headerArr = [];
        if (!empty($this->cfg['--header'])) {
            // 支持多行header，按换行或逗号分割
            $raw = $this->cfg['--header'];
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
        // 执行请求
        $response = curl_exec($ch);
        // 记录请求时间
        $this->last_request_time = microtime(true);
        // 错误代码
        $error_code = curl_errno($ch);
        // 若发生错误，则输出错误日志信息并返回
        if ($error_code) {
            $this->error_count++;
            // 错误信息
            $error_msg = curl_error($ch);
            // 输出错误日志信息
            $this->echo_logs($this->loop_count, $url, "Errno: {$error_code} - {$error_msg}");
            return [false, []];
        }
        // 获取响应信息
        $http_info = curl_getinfo($ch);
        // 若响应状态码不是200，则输出日志信息并返回
        if ($http_info['http_code'] != 200) {
            $this->echo_logs($this->loop_count, $url, 'No Content');
            return [false, []];
        }

        // 输出HTTP请求发送日志信息
        $this->echo_logs($this->loop_count, $url, 'HTTP Requested');
        return [$response, $http_info];
    }

    /**
     * 字符编码转换
     */
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

    /**
     * 等待（秒/微秒）
     */
    public function wait($seconds)
    {
        // 若等待时间小于等于0，则返回
        if ($seconds <= 0) {
            return false;
        }
        // 将秒转换为微秒
        static $microseconds = (int)($seconds * 1000000);

        // 如果上次请求的时间距离当前时间的差值已经大于--wait的值，那就不要休眠了
        if ($this->last_request_time && (microtime(true) - $this->last_request_time) > $microseconds) {
            return false;
        }

        // 根据等待时间的类型进行处理
        if (is_int($seconds) || $seconds == (int)$seconds) {
            // 若等待时间为整数，则使用sleep函数等待
            sleep($seconds);
        } else {
            // 否则，使用usleep函数等待
            usleep($microseconds);
        }
    }

    /**
     * 相对地址和当前URL，转换为绝对地址
     * @param path 页面链接
     * @param url 当前URL
     */
    public function get_absolute_url($path, $url)
    {
        if (empty($url)) return '';
        if (empty($path)) return $url;

        $url_parsed = parse_url($url);
        $scheme = $url_parsed['scheme'] ?? '';
        $host = $url_parsed['host'] ?? '';
        $port = isset($url_parsed['port']) ? ':' . $url_parsed['port'] : '';
        if (empty($scheme) || empty($host)) {
            return '';
        }

        if (str_starts_with($path, '//')) {
            return "{$scheme}:{$path}";
        }
        if (str_starts_with($path, '/')) {
            return "{$scheme}://{$host}{$port}{$path}";
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, 'ftp')) {
            return $path;
        }
        // 到这步若path包含协议头，说明不是支持的协议，则直接返回空
        if (stripos(substr($path, 0, 20), '://') !== false) {
            return '';
        }
        $dirname = str_ends_with($url, '/') ? $url : dirname($url) . '/';
        static $get_absolute_url_cache = [];
        $key = $dirname . $path;
        if (isset($get_absolute_url_cache[$key])) {
            return $get_absolute_url_cache[$key];
        }
        return $get_absolute_url_cache[$key] = $this->get_standard_url($dirname . $path);
        return $this->get_standard_url($dirname . $path);
    }

    /**
     * 绝对路径规范化
     * 将路径中的./、../等部分规范化为标准路径
     * @param string $url 绝对路径
     * @return string 规范化后的绝对路径
     */
    public function get_standard_url($url)
    {
        //上一层函数已做过空字符判断

        $url_parsed = parse_url($url);
        // 当前URL的path部分
        $path = $url_parsed['path'];
        // 步骤1：去除路径中的../
        while (preg_match('/\/[^\/\.]+\/\.\.\//', $path, $match)) {
            $path = str_replace($match, '/', $path);
        }
        // 步骤2：修补，去除路径中的./和..
        while (preg_match('/\/\.{1,2}\//', $path, $match)) {
            $path = str_replace($match, '/', $path);
        }
        // 拼接规范化后的URL
        return (empty($url_parsed['scheme']) ? '' : $url_parsed['scheme'] . '://') .
            (empty($url_parsed['host']) ? '' : $url_parsed['host']) .
            (empty($url_parsed['port']) ? '' : ':' . $url_parsed['port']) .
            $path .
            (empty($url_parsed['query']) ? '' : '?' . $url_parsed['query']);
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
    /**
     * 将链接记录到 SQLite 数据库中（支持批量缓存+定时/定量提交）
     *
     * @param mixed $urls        一条链接字符串或链接数组。若为空则强制刷新缓冲区。
     * @param int|null $use     状态：
     *                           - -1: 插入时不替换旧数据（仅插入新数据）
     *                           - 0/1: 插入或替换旧数据
     * @param bool $batch        是否启用批量缓存，默认 true
     *
     * 示例调用：
     *   saveLinksToDatabase(); // 强制刷新缓冲区
     *   saveLinksToDatabase('http://example.com', -1); // 只添加新记录，不覆盖
     *   saveLinksToDatabase(['url1', 'url2'], 1); // 添加并替换旧记录
     */
    public function saveLinksToDatabase($urls = null, ?int $use = -1, bool $batch = true): void
    {
        if (!extension_loaded('pdo_sqlite')) return;

        // 构建数据库路径
        $dbPath = $this->dir_prefix . DIRECTORY_SEPARATOR . 'pget_' . $this->start_info['host_dir'] . '.db';

        try {
            // 使用静态变量缓存 PDO 连接
            static $pdo = null;
            static $tableInitialized = false;

            if ($pdo === null) {
                // 第一次连接数据库（如果文件不存在会自动创建）
                $pdo = new \PDO("sqlite:$dbPath");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }

            if (!$tableInitialized) {
                // 创建 logs 表（如果不存在）
                $pdo->exec("CREATE TABLE IF NOT EXISTS logs (url TEXT PRIMARY KEY UNIQUE NOT NULL, use_status INTEGER NOT NULL)");
                $tableInitialized = true;
            }

            // 批量模式下使用缓冲区
            if ($batch) {
                // 初始化缓冲区
                static $urlBuffer = [];
                static $lastCommitTime = 0;

                // 如果没有传入 urls，则立即刷新缓冲区并返回
                if ($urls === null) {
                    if (!empty($urlBuffer)) {
                        $pdo->beginTransaction();

                        $this->saveLinksToDatabase_action_sql($pdo, $urlBuffer);

                        $pdo->commit();
                        $this->echo_logs(count($urlBuffer), "URL(s) batch saved to database.");
                        $urlBuffer = []; // 清空缓冲区
                        $lastCommitTime = microtime(true); // 更新最后提交时间
                    }
                    return;
                }

                // 合并传入的URL到缓冲区
                if (is_string($urls)) {
                    if ($use === -1) {
                        // use=-1时，仅当url不在buffer中才添加
                        if (!array_key_exists($urls, $urlBuffer)) {
                            $urlBuffer[$urls] = -1;
                        }
                    } else {
                        // 其他情况正常替换
                        $urlBuffer[$urls] = $use;
                    }
                } elseif (is_array($urls)) {
                    foreach ($urls as $url) {
                        if ($use === -1) {
                            if (!array_key_exists($url, $urlBuffer)) {
                                $urlBuffer[$url] = -1;
                            }
                        } else {
                            $urlBuffer[$url] = $use;
                        }
                    }
                }

                $currentTime = microtime(true);

                // 检查是否满足提交条件
                if (count($urlBuffer) >= 500 || ($lastCommitTime && ($currentTime - $lastCommitTime) >= 300)) {
                    $pdo->beginTransaction();

                    $this->saveLinksToDatabase_action_sql($pdo, $urlBuffer);

                    $pdo->commit();
                    $this->echo_logs(count($urlBuffer), "URL(s) batch saved to database.");
                    $urlBuffer = []; // 清空缓冲区
                    $lastCommitTime = $currentTime; // 更新最后提交时间
                }
            } else {
                // 非批量模式，立即写入数据库
                if ($urls === null) {
                    return; // 不传入 urls 时非批量模式无意义
                }

                $pdo->beginTransaction();

                if (is_string($urls)) {
                    $urlBuffer[$urls] = $use;
                }

                $this->saveLinksToDatabase_action_sql($pdo, $urlBuffer);

                $pdo->commit();
            }
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->echo_logs("Database error: " . $e->getMessage());
        }
    }
    // 插入和替换操作的动作函数
    private function saveLinksToDatabase_action_sql(&$pdo, &$urlBuffer): void
    {

        $ignoreUrls = [];
        $replaceUrls = [];

        foreach ($urlBuffer as $url => $status) {
            if ($status === 1) {
                $replaceUrls[] = ['url' => $url, 'use_status' => $status];
            } else {
                $ignoreUrls[] = ['url' => $url, 'use_status' => $status];
            }
        }
        // 插入 use=-1/0 的记录（不能覆盖use=1的记录）
        if (!empty($ignoreUrls)) {
            $stmtIgnore = $pdo->prepare("INSERT OR IGNORE INTO logs (url, use_status) VALUES (?, ?)");
            foreach ($ignoreUrls as $item) {
                $stmtIgnore->execute([$item['url'], $item['use_status']]);
            }
        }

        // 插入 use=1 的记录（可替换其它旧值）
        if (!empty($replaceUrls)) {
            $stmtReplace = $pdo->prepare("INSERT OR REPLACE INTO logs (url, use_status) VALUES (?, ?)");
            foreach ($replaceUrls as $item) {
                $stmtReplace->execute([$item['url'], $item['use_status']]);
            }
        }
    }
    /**
     * 从 SQLite 数据库加载数据
     * 将 use_status = 0 或 1 的记录加入 link_table
     * 将 use_status = -1 的记录加入 pending_queue
     * 成功返回 true，失败返回 false
     */
    public function loadFromDatabase(): bool
    {
        if (!extension_loaded('pdo_sqlite')) return false;
        // 构建数据库路径
        $dbPath = $this->dir_prefix . DIRECTORY_SEPARATOR . 'pget_' . $this->start_info['host_dir'] . '.db';

        // 检查数据库文件是否存在
        if (!file_exists($dbPath)) {
            $this->echo_logs('Database file not found:', $dbPath);
            return false;
        }

        try {
            // 连接数据库
            $pdo = new \PDO("sqlite:$dbPath");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // 查询 logs 表中的所有数据
            $stmt = $pdo->query("SELECT url, use_status FROM logs");

            if (!$stmt) {
                $this->echo_logs('Failed to query database.');
                return false;
            }

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $this->echo_logs('No records found in the database.');
                return false; // 数据库为空返回 false
            }

            foreach ($rows as $row) {
                $url = $row['url'];
                $status = $row['use_status'];

                if ($status === 1) {
                    // 加入 link_table，本地文件已存在
                    $this->link_table_add_item($url, true);
                } else {
                    // 添加到 pending_queue，URL待处理
                    $this->pending_queue->enqueue($url);
                }
            }

            $this->echo_logs(count($rows), ' records loaded from database.');
            return true;
        } catch (\PDOException $e) {
            $this->echo_logs('Database error during loading:', $e->getMessage());
            return false;
        }
    }
}
// =================== 分片存储 ===================
/* 
 * 数组分片存储类
 * 默认将数据均匀分配到256个子数组中存储
 */
class ArraySharder
{
    // 存储所有分片的数组
    public $shards = [];

    // 分片前缀
    const SHARD_PREFIX = 'shard_';

    public function __construct()
    {
        // 初始化256个分片数组 (00-FF)
        for ($i = 0; $i < 256; $i++) {

            $shardName = self::SHARD_PREFIX . $i;
            $this->shards[$shardName] = [];
        }
    }

    // 核心分片函数 (基于crc32)
    public function getShardName(string $input): string
    {
        return self::SHARD_PREFIX . (crc32($input) & 0xFF); // substr(hash("crc32b", $input), 0, 2);
    }

    // 获取分片引用 (直接操作)
    public function &getShard(string $input): array
    {
        $shardName = $this->getShardName($input);
        if (!isset($this->shards[$shardName])) {
            $this->shards[$shardName] = [];
        }
        return $this->shards[$shardName];
    }

    // 添加键值对到分片
    public function addItem(string $input, mixed $data): void
    {
        $shard = &$this->getShard($input);
        $shard[$input] = $data;
    }

    // 获取键值对数据
    public function getItem(string $input): mixed
    {
        $shard = $this->getShard($input);
        return $shard[$input] ?? null;
    }

    // 删除键值对
    public function removeItem(string $input): bool
    {
        $shard = &$this->getShard($input);

        if (isset($shard[$input])) {
            unset($shard[$input]);
            return true;
        }

        return false;
    }

    // 获取所有分片统计
    public function getStats(): array
    {
        $stats = [];
        foreach ($this->shards as $name => $data) {
            $stats[$name] = count($data);
        }
        return $stats;
    }

    // 获取总数量
    public function count()
    {
        return array_sum($this->getStats());
    }

    // 调试
    public function debug()
    {
        print_r($this->shards);
        echo "[DEBUG] 已有 shardName 列表:\n";
        foreach (array_keys($this->shards) as $name) {
            echo "  - {$name}\n";
        }
        // 你也可以输出 $this->shards 的数量
        echo "[DEBUG] 当前 shards 总数: " . $this->count() . "\n";
    }
}
// =================== 工具函数 ===================

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
    if (preg_match('/^[A-Za-z0-9\-._~:\/?#\[\]@!$&\'()*+,;=%]*$/', $url)) {
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
/**
 * 判断多维数组是否为空
 * @param mixed $value
 * @return bool
 */
function is_multi_array_empty($value)
{
    if (is_array($value)) {
        // 遍历数组元素
        foreach ($value as $v) {
            // 递归判断元素是否为空
            if (!is_multi_array_empty($v)) {
                return false;
            }
        }
        return true;
    }
    // 判断非数组元素是否为空
    return $value === '' || $value === null || $value === [] || $value === false;
}

// =================== 错误提示 ===================
// 致命错误处理函数
function pget_shutdown_handler()
{
    global $pget;
    $error = error_get_last();
    $usage_mb = round(memory_get_usage() / 1024 / 1024, 2);
    // 使用配置中的目录前缀
    $dir_prefix = isset($pget) && isset($pget->cfg['--directory-prefix']) ? $pget->cfg['--directory-prefix'] : __DIR__;
    $log_file = $dir_prefix . '/pget_shutdown.log';
    $url = $pget->cfg['--start-url'] ?? '';
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

    // 利用析构函数来清理资源
    if (isset($pget)) {
        // 确保日志被记录和刷新
        $pget->echo_logs($msg);
        $pget->flush_log_buffer();
        // 对于致命错误，显式调用析构函数确保资源释放
        // 因为在致命错误情况下，PHP可能不会正常调用析构函数
        if ($error) {
            $pget->__destruct();
        }
    }
}

// 信号处理公共函数，信号处理PHP会正常执行清理流程，包括调用析构函数
function pget_signal_handler($signal_type, $signal_name, $exit_code)
{
    global $pget;
    $usage_mb = round(memory_get_usage() / 1024 / 1024, 2);
    $now = date('Y-m-d H:i:s');
    // 使用配置中的目录前缀
    $dir_prefix = isset($pget) && isset($pget->cfg['--directory-prefix']) ? $pget->cfg['--directory-prefix'] : __DIR__;
    $log_file = $dir_prefix . '/pget_shutdown.log';
    $url = $pget->cfg['--start-url'] ?? '';
    $msg = "[SIGNAL] {$now} [{$signal_type}: {$signal_name}]\n";
    $msg .= "  起始URL: {$url}\n";
    $msg .= "  日志文件: {$log_file}\n";
    $msg .= "  内存占用: {$usage_mb} MB\n";
    $msg .= "  说明: 收到 {$signal_name} 信号，脚本被" . ($signal_name === 'SIGINT' ? '用户中断' : '外部终止') . "。\n";
    echo "\n========== 脚本被" . ($signal_name === 'SIGINT' ? '用户中断 (Ctrl+C)' : '外部终止 (SIGTERM)') . " ==========\n";
    echo $msg;
    file_put_contents($log_file, $msg, FILE_APPEND);
    // 处理资源清理
    if (isset($pget)) {
        // 确保日志被记录和刷新
        $pget->echo_logs($msg);
        $pget->flush_log_buffer();
    }
    // 信号处理后正常退出，PHP会自动调用析构函数
    exit($exit_code);
}
