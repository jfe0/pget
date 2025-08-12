<?php

// 命令行参数列表，定制初始化配置
$param_list = [
    '', // 控制代替脚本自名
    '--start-url=http://www.ccbbp.com/',
    '--directory-prefix=C:\\workspace\\wwwcrawler',
    '--reject-regex=\?|#|&|(\.rar)|(\.zip)|(\.epub)|(\.txt)|(\.pdf)',
    '--wait=5',
    '--max-threads=20',
    '--recursive',
    '--no-clobber',
    '--page-requisites',
    '--adjust-extension',
    '--no-check-certificate',
    '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    '--tries=1000',
    '--pause-time=3600',
    '--pause-tries=5'
];

/**
提示：注意看注释哦！

 * 中文说明：
 * 
 * Pget2.php - Pget的并发版
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

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
if (php_sapi_name() !== 'cli') die("Only be run in CLI mode.\n"); // 检查是否在命令行模式下运行
if (version_compare(PHP_VERSION, '8.0.0', '<')) die("PHP 8.0+ Required.\n"); // PHP版本检查
if (!extension_loaded('pdo_sqlite')) echo "pdo_sqlite extension is not enabled. logs wiil not be using.\n"; // 检查 PDO SQLite 是否可用
error_reporting(E_ALL & ~E_NOTICE); // 只显示除了通知之外的所有错误
set_time_limit(0); // 设置脚本执行时间无限制
ignore_user_abort(1); // 忽略用户断开连接，确保脚本继续执行
ini_set('memory_limit', '20480M'); // 设置脚本可使用的最大内存为20G
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
        '--user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        '--header' => '',
        '--save-cookies' => '',
        '--load-cookies' => '',
        '--keep-session-cookies' => 0,
        '--output-file' => '',
        '--force-directories' => 0,
        '--tries' => 20,
        '--retry-connrefused' => 0,
        '--level' => 5,
        '--max-threads' => 0,
        '--pause-time' => 0,
        '--pause-interval' => 0,
        '--pause-tries' => 0,
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
                    } else {
                        // 其他参数直接赋值
                        $this->options[$config_name] = $config_value;
                    }
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
        if (!is_numeric($this->options['--max-threads']) || $this->options['--max-threads'] < 0) {
            throw new \InvalidArgumentException('--max-threads must be a non-negative number.' . PHP_EOL);
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
        $this->echo_logs('FORCEECHO', $this->cfg['--start-url'], $this->link_table->count(), 'Gettings Finished At', date('Y-m-d H:i:s'));
    }

    /**
     * 单个URL请求
     * 只下载一个URL
     */
    private function singleRequest($url)
    {
        // 调用下载并保存内容的方法
        $this->catcher_url_to_local($url);
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
            // 读取所有URL到待处理队列
            while (($url = fgets($handle)) !== false) {
                $url = trim($url);
                if ($url === '') continue;
                $this->pending_queue->enqueue($url);
            }
            fclose($handle);
            if ($this->cfg['--max-threads'] > 1) {
                // 启动并发下载器
                $this->run_multicatcher();
            } else {
                $this->run_singlecatcher();
            }
            $this->flush_log_buffer();
        } else {
            $this->echo_logs('Failed to open input file: ' . $filename);
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
        // 入队起始链接
        if ($this->path_filter_all($url)) {
            $this->add_url_action_if_new($url, 'enqueue');
        } else {
            return false;
        }
        // 若设置不覆盖本地文件，则读取本地文件
        if ($this->cfg['--no-clobber']) {
            // 尝试从数据库加载已有数据
            if (!$this->loadFromDatabase()) {
                $this->echo_logs('Failed to load data from database.');

                $this->start_once();
            }
        }

        if ($this->cfg['--max-threads'] > 1) {
            // 启动并发下载器
            $this->run_multicatcher();
        } else {
            $this->run_singlecatcher();
        }
        // 最后一次写入日志
        $this->flush_log_buffer();
        // 检查并处理信号
        if (function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();
    }

    /* 
     * 首次启动检查数据库文件和本地文件
    */
    public function start_once()
    {
        $this->echo_logs('Loading existing files from local directory...');

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

                    // 标记本地文件对应的URL为true（文件已存在），对URL进行编码
                    $this->add_linktable_url_status($url, true);

                    // 读取文件内容，处理链接和资源
                    $response = file_get_contents($file_path);
                    // 提取页面链接
                    $links = $this->get_page_links($response, $url);
                    // 处理页面链接，加入队列（不用加入链接表）
                    $this->add_enqueue_links($links);
                    // 输出请求日志信息
                    $this->echo_logs('FORCEECHO', "{$file_path} -> {$url}",  'Found, Links Add');
                }
            }
        }
        // 刷新日志缓存
        $this->flush_log_buffer();
        // 刷新缓存到数据库
        $this->saveLinksToDatabase();
        // 释放资源
        unset($rii);
        unset($file);
        $this->echo_logs($this->link_table->count(), 'Files Found.' . PHP_EOL);
    }
    /**
     * 将链接记录到 SQLite 数据库中（支持批量缓存+定时/定量提交）
     *
     * @param mixed $urls        一条链接字符串或链接数组。若为空则强制刷新缓冲区。
     * @param int|null $use     状态：
     *                           - -1: 待处理链接。若已存在则跳过重复插入。
     *                           - 0/1: 已处理链接。插入链接或替换旧状态
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

                        $this->saveLinksToDatabase_action($pdo, $urlBuffer);

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

                    $this->saveLinksToDatabase_action($pdo, $urlBuffer);

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

                $this->saveLinksToDatabase_action($pdo, $urlBuffer);

                $pdo->commit();
            }
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->echo_logs("Database error: " . $e->getMessage());
        }
    }
    /* 
     * 插入和替换操作的动作函数
     * 不要再此处对URL编码，在传入前统一处理
     *  */
    private function saveLinksToDatabase_action(&$pdo, &$urlBuffer): void
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
        // 若待插入是 use=-1/0 的记录，则插入或忽略，防止覆盖use=1的记录
        if (!empty($ignoreUrls)) {
            $stmtIgnore = $pdo->prepare("INSERT OR IGNORE INTO logs (url, use_status) VALUES (?, ?)");
            foreach ($ignoreUrls as $item) {
                $stmtIgnore->execute([$item['url'], $item['use_status']]);
            }
        }

        // 若待插入是 use=1 的记录，则可替换其它旧值
        if (!empty($replaceUrls)) {
            $stmtReplace = $pdo->prepare("INSERT OR REPLACE INTO logs (url, use_status) VALUES (?, ?)");
            foreach ($replaceUrls as $item) {
                $stmtReplace->execute([$item['url'], $item['use_status']]);
            }
        }
    }
    /**
     * 从 SQLite 数据库加载数据
     * 数据库中的URL已编码过，加载到连接表和队列时无需再次编码
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
                    $this->add_linktable_url_status($url, true);
                } elseif ($status === 0) {
                    // 加入 link_table，表明已处理过但本地文件不存在（404）
                    // 这里不需要再次下载，直接标记为已处理
                    $this->add_linktable_url_status($url, false);
                } elseif ($status === -1) {
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

    /**
     * 单次下载并保存内容，处理页面资源和链接
     * 1. 判断是否需要下载
     * 2. 下载内容，自动补全扩展名
     * 3. 处理依赖资源和递归链接
     * 4. 保存到本地
     * 5. 记录到临时表
     * 6. 为避免频繁的parse_url，故将解析后的数组作为参数传递
     */
    public function catcher_url_to_local($url)
    {
        $number = $this->loop_count++;
        // 检查URL是否需要过滤
        if (!$this->path_filter_all($url)) {
            // 若URL被过滤，则输出过滤日志信息并返回
            $this->echo_logs($number, $url, 'Reject');
            return false;
        }

        if ($this->error_count > $this->cfg['--tries']) {
            // 如果连续错误超过--tries次，则退出循环
            $this->echo_logs("{$this->error_count} error tries, exit");
            throw new \RuntimeException("{$this->error_count} error tries, exit");
        }

        // 输出请求日志信息
        $this->echo_logs($number, $url, date('Y-m-d H:i:s'), 'Getting');

        // 检查本地文件是否存在，默认为null，保持和 link_table 3中状态一致
        $is_file_exist = null;

        // 检查本地文件是否存在
        if ($this->should_skip_download($url, $is_file_exist, $number)) {
            return true;
        }

        // 该检查的已经检查，到这步就添加到下载任务了

        // 发起网络请求
        list($response, $http_info) = $this->get($url);
        // 处理下载结果
        $this->handle_downloaded_content($url, $response, $http_info, $number, $is_file_exist);

        // 若设置了操作间隔时间，则进行等待
        if ($this->cfg['--wait']) {
            $this->echo_logs('Waiting ' . $this->cfg['--wait'] . ' seconds');
            $this->wait($this->cfg['--wait']);
        }

        return true;
    }
    /**
     * 将新发现的链接加入待处理队列
     * 处理URL数组
     */
    public function add_enqueue_links($links)
    {
        // 遍历链接数组
        foreach ($links as $url) {
            // 若链接未处理过，且是允许的链接，则加入队列
            $this->add_url_action_if_new($url, 'enqueue');
        }
    }
    /**
     * 判断是否为新链接，并添加链接表或队列
     * 统一处理新链接：入口
     * @param string $url
     * @param string $action
     */
    public function add_url_action_if_new($url, $action)
    {
        // 统一处理链接过滤，跳过不允许的链接
        if (!$this->path_filter_all($url)) return false;

        if ($action === 'enqueue') {
            $this->add_enqueue_if_new($url);
        } elseif ($action === 'linktable') {
            $this->add_linktable_if_new($url, false);
        }
        return true;
    }
    /**
     * 判断为新链接则入链接表
     * */
    private function add_linktable_if_new($url)
    {
        if ($this->get_linktable_url_status($url) === null) {
            $this->add_linktable_url_status($url, false);
            // 保存日志位于add_linktable_url_status
        }
    }
    /**
     * 统一处理新链接入队列
     * 判断为新链接则入队列
     * 保存日志
     * @param string $url
     *  */
    private function add_enqueue_if_new($url)
    {
        if ($this->get_linktable_url_status($url) === null) {
            $this->pending_queue->enqueue($url);
            // 保存日志
            $this->saveLinksToDatabase($url, -1);
        }
    }
    /**
     * 从链接表取出链接加入队列（link_table中值有3种状态：null(不存在)、false、true）
     * 链接表中URL是编码的 
     * */
    private function add_linktable_to_queue(): void
    {
        // 将链接表中的false值（文件不存在）加入队列，用于重试下载
        foreach ($this->link_table->shards as $shard) {
            foreach ($shard as $url => $use) {
                if ($use === true) continue; // 跳过已存在的文件
                $this->add_url_action_if_new(rawurldecode($url), 'enqueue');
            }
        }
    }
    /**
     * 统一处理链接表入表
     * 新链接不要用，而是用add_linktable_if_new
     * 保存日志
     */
    private function add_linktable_url_status($url, bool $value)
    {
        $this->link_table->addItem($url, $value);
        // 保存到日志
        $this->saveLinksToDatabase($url, (int)$value);
    }
    /**
     * 统一处理链接表取值
     */
    private function get_linktable_url_status($url)
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
        $flushlog = false;
        if ($args[0] === 'FORCEECHO') {
            $stdout = true;
            $flushlog = true;
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
        $url_host = parse_url($url, PHP_URL_HOST);
        if (empty($url_host)) {
            return false;
        }
        $url_host = strtolower($url_host);
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
     * @param url 传入链接或者parse_url解析后的数组
     * 目录名包含端口号，兼容Win/Linux，支持特殊字符转义
     */
    public function url_local_path($url, $dir_prefix): string
    {
        if (empty($url)) {
            return '';
        }
        $url_parsed = is_array($url) ? $url : parse_url($url);
        $path = $url_parsed['path'] ?? '/';
        if (!empty($this->cfg['--level'])) {
            $trimmed = trim($path, '/');
            if ($trimmed !== '') {
                $depth = substr_count($trimmed, '/');
                if ($depth > $this->cfg['--level']) {
                    $this->echo_logs($this->loop_count, $url, 'Max depth exceeded.');
                    return '';
                }
            }
        }

        if (str_ends_with($path, '/')) {
            $path .= 'index.html';
        }
        $decodedPath = rawurldecode($path);
        $query = empty($url_parsed['query']) ? '' : '?' . str_replace('/', '%2F', rawurldecode($url_parsed['query']));
        $file_path = $decodedPath . $query;
        $file_path = ltrim($file_path, '/');
        if (!empty($this->cfg['--restrict-file-names'])) {
            $file_path = rawurlencodex($file_path);
        }
        static $invalidChars = [
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
        return
            $dir_prefix .
            DIRECTORY_SEPARATOR .
            $url_parsed['host'] .
            (empty($url_parsed['port']) ? '' : '%3A' . $url_parsed['port']) .
            DIRECTORY_SEPARATOR .
            $file_path;
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
        $microseconds = (int)($seconds * 1000000);

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

    // ================== 并发爬虫 ==================

    //正在采集的句柄集
    private $handleMap;
    //总采集句柄
    private $chs;

    /**
     * 串行采集：GET方式
     *
     * @param unknown $url 要采集的地址
     * @param string $referer
     * @param string $proxy
     * @param string $cookie
     * @param string $header
     * @return string html_body
     */
    public function get($url): array
    {

        $ch = $this->createHandle($url);

        // 获取 cURL 错误码
        $curl_errno = curl_errno($ch);
        // 获取 cURL 错误信息
        $curl_error = curl_error($ch);
        // 开始抓取
        $response = curl_exec($ch);
        $chInfo = curl_getinfo($ch);
        // 关闭连接
        curl_close($ch);

        // 请求出错或反馈内容为空
        if ($curl_errno !== 0 || $code === 403) {
            $this->error_count++;
            $this->error($ch, "Error Code : {$curl_errno}\tMessage : {$curl_error}");
            return [false, []];
        }

        // 返回结果
        return [$response, $chInfo];
    }
    /**
     * 串行采集：POST方式
     * @param unknown $urls
     */
    private function post($url, $post_array) {}
    /**
     * 创建一个抓取句柄
     * @param unknown $url 要抓取的地址
     * @param string $referer
     * @return multitype:resource Ambigous
     */
    private function createHandle($url, $method = 'GET', $postfields = [])
    {
        // 待请求的URL要编码，curl无法处理非ASCII网址
        $url = rawurlencodex($url);
        //构造一个句柄
        $ch = curl_init();

        //构造配置
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_RETURNTRANSFER => 1, // 要求返回结果
            CURLOPT_CONNECTTIMEOUT => 5, //连接超时
            CURLOPT_TIMEOUT => 5, // 超时
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE, // 自动 http 协议
            CURLOPT_FOLLOWLOCATION => true, // 是否自动 301/302跳转
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => $this->cfg['--user-agent']
        );
        curl_setopt_array($ch, $opt);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        if (str_starts_with($url, 'https')) {
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
        if (!empty($postfields)) {
            // 若有POST数据，则设置为POST请求
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        } else {
            // 否则为GET请求
            curl_setopt($ch, CURLOPT_POST, false);
        }
        // 支持多行header，按换行或逗号分割
        if ($this->cfg['--header']) {
            $headerArr = [];
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
            if ($headerArr) {
                // 设置HTTP请求头
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
            }
        }

        if (!empty($this->cfg['--save-cookies'])) {
            // 生成cookie文件路径
            $cookiejar = $this->dir_prefix . DIRECTORY_SEPARATOR . $this->cfg['--save-cookies'];
            // 设置cookie文件
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
        }

        return $ch;
    }
    /**
     * 添加一个异步任务
     * @param 采集地址 $url
     */
    private function pushJob($url): void
    {
        // 检查URL是否需要过滤

        $this->add_url_action_if_new($url, 'enqueue');
        $this->add_url_action_if_new($url, 'linktable');
    }

    /**
     * 从待采集任务栈中取任务,加入正在采集的任务集
     * URL不需要编码，因为在添加队列时已经编码过了
     */
    private function fillMap(): void
    {

        // 超过最大重试次数就停止
        static $pause_tries_count = 0;
        static $last_pause_time = 0;

        if ($this->error_count > $this->cfg['--tries']) {
            // 互斥逻辑：如果 pause-tries 和 pause-interval 都配置，则只用 pause-tries
            if ($this->cfg['--pause-tries'] > 0) {
                // 最大暂停次数逻辑
                if ($pause_tries_count < $this->cfg['--pause-tries'] && $this->cfg['--pause-time'] > 0) {
                    $this->echo_logs('FORCEECHO', "Error limit reached, pausing for {$this->cfg['--pause-time']}s (pause #" . ($pause_tries_count + 1) . ")");
                    sleep($this->cfg['--pause-time']);
                    $pause_tries_count++;
                    $this->error_count = 0; // 归零错误次数
                } else {
                    $this->echo_logs("ERROR: Too many retries (pause limit reached)");
                    $this->flush_log_buffer();
                    throw new \Exception("ERROR: Too many retries");
                }
            } elseif ($this->cfg['--pause-interval'] > 0 && $this->cfg['--pause-time'] > 0) {
                // 按照间隔逻辑
                $now = time();
                if ($now - $last_pause_time > $this->cfg['--pause-interval']) {
                    $this->echo_logs('FORCEECHO', "Error limit reached, pausing for {$this->cfg['--pause-time']}s (interval mode)");
                    sleep($this->cfg['--pause-time']);
                    $last_pause_time = $now;
                    $this->error_count = 0; // 归零错误次数
                } else {
                    $this->echo_logs("ERROR: Too many retries (interval not reached)");
                    $this->flush_log_buffer();
                    throw new \Exception("ERROR: Too many retries");
                }
            } else {
                // 没有配置暂停参数，直接抛出异常
                $this->echo_logs("ERROR: Too many retries (no pause configured)");
                $this->flush_log_buffer();
                throw new \Exception("ERROR: Too many retries");
            }
        }

        //从待处理列表中取信息到正在处理的列表中
        while (count($this->handleMap) < $this->cfg['--max-threads'] && $this->pending_queue->isEmpty() === false) {
            $number = $this->loop_count++;
            $url = $this->pending_queue->dequeue();

            // ======== 下载前的处理过程 =========

            // 检查URL是否需要过滤
            if (!$this->path_filter_all($url)) {
                // 若URL被过滤，则输出过滤日志信息并返回
                $this->echo_logs($number, $url, 'Reject');
                continue;
            }
            if ($this->error_count > $this->cfg['--tries']) {
                // 如果连续错误超过--tries次，则退出循环
                $this->echo_logs("{$this->error_count} error tries, exit");
                throw new \Exception("{$this->error_count} error tries, exit");
            }

            // 检查本地文件是否存在，默认为null，保持和 link_table 3中状态一致
            $is_file_exist = null;

            // 检查本地文件是否存在
            if ($this->should_skip_download($url, $is_file_exist, $number)) {
                continue;
            }

            // 该检查的已经检查，到这步就添加到下载任务了
            // 不要将URL添加到链接表，而是在后续sucess和error方法中处理

            // ==========下载前处理过程：结束=========

            // 创建子句柄
            $ch = $this->createHandle($url);
            //加到总句柄中
            curl_multi_add_handle($this->chs, $ch);
            //记录到正在处理的句柄中
            $this->handleMap[$ch] = ['url' => $url, 'is_file_exist' => $is_file_exist, 'id' => $number];

            // 输出请求日志信息
            $this->echo_logs($number, date('Y-m-d H:i:s'), $url, 'Task Add');
        }
    }

    /**
     * 处理一个已经采集到的任务
     * @param unknown $done
     */
    private function done($done)
    {
        //子句柄
        $ch = $done['handle'];

        // 解析重定向链
        /* 
        $meta = $this->handleMap[$ch];
        if (curl_getinfo($ch, CURLINFO_REDIRECT_COUNT) > 0) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr(curl_multi_getcontent($ch), 0, $headerSize);

            // 解析Location头构建完整重定向链
            preg_match_all('/Location: (.*)/i', $headers, $matches);
            $meta['redirect_chain'] = array_merge(
                [$meta['url']],
                array_map('trim', $matches[1])
            );
        } 
        */

        // 获取 cURL 错误码
        $curl_errno = curl_errno($ch);
        // 获取 cURL 错误信息
        $curl_error = curl_error($ch);

        //curl信息
        $http_info = curl_getinfo($ch);
        // 最终访问的URL，如果允许了跳转则此处不等于原始URL
        // $effectiveUrl = $chInfo['url'];
        // 获取原始URL
        // $url = $this->handleMap[$ch]['url'];
        //采集到的内容
        $response = curl_multi_getcontent($ch);

        // 请求出错或反馈内容为空
        if ($curl_errno !== 0) {
            $this->error($ch, "Error Code : {$curl_errno}\tMessage : {$curl_error}");
        } else {
            $this->success($ch, $response, $http_info);
        }
    }
    public function run_singlecatcher()
    {
        try {
            // 当最大线程数≤1时，退化为串行模式
            $this->echo_logs('FORCEECHO', 'Running in serial mode (max-threads <= 1)');
            // 主循环
            while ($this->pending_queue->isEmpty() === false) {
                // 从队列中取出一个URL
                $url = $this->pending_queue->dequeue();

                // 单次下载并保存内容的方法
                $this->catcher_url_to_local($url);

                // 每一轮操作后写入日志
                $this->flush_log_buffer();
                // 检查并处理信号
                if (function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();
            }
        } catch (Throwable $e) {
            $this->echo_logs($this->cfg['--start-url'], "SYSTEM ERROR: " . $e->getMessage());
            curl_multi_close($this->chs);
            throw $e; // 重新抛出异常
        }
        // 最后一次日志缓存刷新
        $this->flush_log_buffer();
    }
    /**
     * 任务入栈后,开始并发采集
     */
    public function run_multicatcher()
    {
        try {
            // 并发模式才会初始化这两个变量
            // 并发句柄集
            $this->chs = curl_multi_init();
            // 正在处理的句柄映射，对象键名数组
            $this->handleMap = new SplObjectStorage();

            // 最外层的循环用于每轮任务完成后的休眠（不能在主循环和填充任务队列时进行休眠，会导致错误次数超过阈值和每个任务后都休眠）
            while ($this->pending_queue->isEmpty() === false) {
                // 初始填充任务或统一补充任务
                $this->fillMap();

                $active = null;
                $status = CURLM_OK;

                // 主循环
                do {
                    // === 关键修复1：添加初始任务处理 ===
                    do {
                        $status = curl_multi_exec($this->chs, $active);
                    } while ($status === CURLM_CALL_MULTI_PERFORM);

                    // === 关键修复2：正确处理无活动任务状态 ===
                    if ($status !== CURLM_OK) break;

                    // === 关键修复3：添加等待机制 ===
                    $ready = curl_multi_select($this->chs, 0.5);
                    if ($ready === -1) {
                        usleep(10000); // 10ms
                        continue;
                    }

                    // === 关键修复4：处理完成的任务 ===
                    while ($done = curl_multi_info_read($this->chs)) {
                        if ($done['msg'] !== CURLMSG_DONE) continue;

                        $this->done($done);

                        $ch = $done['handle'];
                        $this->handleMap->detach($ch);
                        curl_multi_remove_handle($this->chs, $ch);
                        curl_close($ch);

                        // 如果开启等待模式，则此时不需要补充任务，而是等待所有任务完成统一补充任务
                        if (!$this->cfg['--wait']) {
                            // 补充新任务
                            $this->fillMap();
                        }
                    }

                    // 检查退出条件
                } while ($active || count($this->handleMap));

                // 在每轮任务完成后刷新日志
                $this->flush_log_buffer();

                // 若设置了操作间隔时间，则进行等待。等待功能不能在 curl_multi_* 处理结构内部，会造成并发请求超时错误
                if ($this->cfg['--wait']) {
                    $this->echo_logs('FORCEECHO', 'Waiting for ' . $this->cfg['--wait'] . ' seconds...');
                    $this->wait($this->cfg['--wait']);
                }
            }
            // 释放资源
            curl_multi_close($this->chs);
            unset($this->handleMap);
        } catch (Throwable $e) {
            $this->echo_logs($this->cfg['--start-url'], "SYSTEM ERROR: " . $e->getMessage());
            curl_multi_close($this->chs);
            $this->flush_log_buffer(); // 异常时刷新日志
            throw $e; // 重新抛出异常
        }
    }
    /**
     * 创建目录并处理可能的文件冲突
     * @param string $local_file 本地文件路径
     * @param int $ taskId 任务ID（用于日志输出）
     * @return bool 目录创建成功返回true，失败抛出异常
     * @throws Exception 当目录创建失败时抛出异常
     */
    private function createDirectoryForFile(string $local_file, string $url, int $taskId): bool
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

                    // URL目录
                    $url_dir = rtrim(dirname($url), '/');

                    // 如果存在同名文件，则尝试重命名为"目录名.html"
                    if ($this->get_linktable_url_status($url_dir) === true || is_file($local_dir)) {
                        // 文件名末尾添加数字后缀再尝试写入
                        $suffix = 1;
                        $new_local_file = $local_dir . '_' . $suffix;
                        $new_url_dir = $url_dir . '_' . $suffix;
                        // 优先用链接表检查新文件名是否存在，链接表不存在时再检查文件系统
                        while (
                            file_exists($new_local_file) ||
                            is_dir($new_local_file)
                        ) {
                            $suffix++;
                            $new_local_file = $local_dir . '_' . $suffix;
                            $new_url_dir = $url_dir . '_' . $suffix;
                        }
                        if (rename($local_dir, $new_local_file)) {
                            $this->add_linktable_url_status($new_url_dir, true); // 新URL对应文件

                            $this->echo_logs('FORCEECHO', $taskId, 'Renamed file "' . $local_dir . '" to "' . $new_local_file . '" to make way for directory creation');

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
                            $this->echo_logs('FORCEECHO', $taskId, 'Failed to rename conflicting file "' . $local_dir . '" to "' . $new_local_file . '"');
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
     * 写入文件内容并处理可能的错误
     * 
     * @param string $local_file 本地文件路径
     * @param string $response 要写入的内容
     * @param string $url 原始URL
     * @param int $number 任务编号
     * @return bool 写入成功返回true，失败返回false
     */
    private function writeContentToFile(string $local_file, string $response, string $url, int $number): bool
    {

        // 获取本地文件所在目录并创建
        $this->createDirectoryForFile($local_file, $url, $number);

        // 清除之前的错误信息
        error_clear_last();
        // 确保目录存在，前文已经处理了目录创建和冲突问题

        if (@file_put_contents($local_file, $response) === false) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'Unknown error';

            // 判断错误类型并输出说明
            if (strpos($errorMessage, 'Permission denied') !== false || strpos($errorMessage, 'errno 13') !== false) {
                $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), $url, 'Failed to write file (Permission denied): ' . $local_file);
            } elseif (strpos($errorMessage, 'No space left on device') !== false || strpos($errorMessage, 'errno 28') !== false) {
                $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), $url, 'Failed to write file (No space left): ' . $local_file);
            } else {
                $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), $url, 'Failed to write file: ' . $local_file . ' - Error: ' . $errorMessage);
            }

            // 检查是否存在同名文件夹
            if ($this->get_linktable_url_status($url) === true || is_dir($local_file)) {
                // 文件名末尾添加数字后缀再尝试写入
                $suffix = 1;
                $new_local_file = $local_file . '_' . $suffix;
                // 优先用链接表检查新文件名是否存在，链接表不存在时再检查文件系统
                while (
                    file_exists($new_local_file) ||
                    is_dir($new_local_file)
                ) {
                    $suffix++;
                    $new_local_file = $local_file . '_' . $suffix;
                }
                if (@file_put_contents($new_local_file, $response) !== false) {
                    $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), "{$url} -> {$new_local_file}", 'Saved with suffix');
                    $this->add_linktable_url_status($url, true);
                    return true;
                } else {
                    $error2 = error_get_last();
                    $errorMessage2 = $error2['message'] ?? 'Unknown error';
                    $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), $url, 'Failed to write file with suffix: ' . $new_local_file . ' - Error: ' . $errorMessage2);
                    return false;
                }
            } else {
                return false;
            }
        } else {
            // 写入成功，输出日志
            $this->echo_logs('FORCEECHO', $number, date('Y-m-d H:i:s'), "{$url} -> {$local_file}", 'Saved');
        }

        return true;
    }

    /**
     * 检查本地文件是否存在及是否应跳过下载
     * 不要对URL编码，要在传入前统一处理
     * @param string $url
     * @param mixed &$is_file_exist
     * @param int $number
     * @return bool true=应跳过下载，false=应继续下载
     */
    private function should_skip_download($url, &$is_file_exist, $number): bool
    {
        // 检查本地文件是否存在，使用链接表代替实时 file_exists 大批量时影响性能
        $is_file_exist = $this->get_linktable_url_status($url);
        // 本地文件存在的情况
        if ($is_file_exist === true) {
            // 判断是否需要重新下载
            if ($this->cfg['--no-clobber']) {
                $this->echo_logs($number, date('Y-m-d H:i:s'), $url, 'Exists, Skip');
                return true; // 文件已存在且不需要重新下载，直接返回
            }
        }
        // 本地文件不存在，检查目录链接和自动扩展名的情况
        else {
            // URL目录链接处理
            if (str_ends_with($url, '/')) {
                $is_file_exist = $this->get_linktable_url_status($url . 'index.html');
            }
            // 自动扩展名的情况处理，默认测试html扩展名
            elseif (empty(get_ext($url)) && $this->cfg['--adjust-extension']) {
                // 检查是否存在 .html 扩展名的链接
                $is_file_exist = $this->get_linktable_url_status($url . '.html');
            }
            // 判断是否需要重新下载
            if ($is_file_exist === true) {
                // 调整后的URL存在，则将原URL标记为true，新URL不用添加到链接表，避免冗余
                $this->add_linktable_url_status($url, true);
                // 判断是否需要重新下载
                if ($this->cfg['--no-clobber']) {
                    $this->echo_logs($number, date('Y-m-d H:i:s'), $url, 'Exists, Skip');
                    return true; // 文件已存在且不需要重新下载，直接返回
                }
            }
        }
        // 如果到这里，说明文件不存在或需要重新下载
        return false;
    }
    /**
     * 处理下载到的内容
     * 
     * @param string $url 原始URL
     * @param string|false $response 下载的内容
     * @param array $http_info cURL获取的信息数组
     * @param int $number 任务编号
     * @param bool $is_file_exist 本地文件是否已存在
     * @return bool 处理成功返回true，失败返回false
     */
    private function handle_downloaded_content($url, $response, $http_info, $number, $is_file_exist)
    {
        // 输出状态码（串行模式可不输出）
        if (isset($http_info['http_code'])) {
            $this->echo_logs($number, $url, "Http_Code : {$http_info['http_code']}");
        }

        $url_parsed = parse_url($url);
        $sub_string_rules = $this->config->sub_string_rules;

        // 输出主机IP信息
        static $host_ip = [];
        if (isset($url_parsed['host'])) {
            if (isset($host_ip[$url_parsed['host']])) {
                $ip = $host_ip[$url_parsed['host']];
            } else {
                $ip = gethostbyname($url_parsed['host']);
                $host_ip[$url_parsed['host']] = $ip;
            }
            $this->echo_logs($number, "{$url_parsed['host']} -> {$ip}");
        }

        // 若响应内容为空，则输出日志信息并返回
        if ($response === false) {
            if ($is_file_exist === null) $this->add_linktable_url_status($url, false); // 标记false，文件不存在，但url已处理
            $this->echo_logs($number, $url, 'Response Null');
            return false;
        }

        // 若设置了转换为UTF-8编码，则进行编码转换
        if ($this->cfg['--utf-8']) {
            $response = $this->mb_encode($response);
        }
        // 若设置了镜像或递归下载，则提取并处理页面链接
        if ($this->cfg['--mirror'] || $this->cfg['--recursive']) {
            $links = $this->get_page_links($response, $url);
            $this->add_enqueue_links($links);
            $this->echo_logs($number, $url,  'Links Add');
        }
        // 若设置了内容截取，则进行内容截取
        if (!empty($this->cfg['--sub-string'])) {
            $response = $this->sub_content_all($response, $sub_string_rules);
            $this->echo_logs($number, $url,  'Response Cut');
            if (empty($response)) {
                if ($is_file_exist === null) $this->add_linktable_url_status($url, false); // 标记false，文件不存在，但url已处理
                $this->echo_logs($number, $url, 'Response substring Null');
                return false;
            }
        }
        // 生成本地保存路径，目录会自动添加index.html
        $local_file = $this->url_local_path($url, $this->dir_prefix);
        // 本地文件名不存在时跳过，只要URL不为空，则local_file不会为空
        // if (empty($local_file)) return false;
        // 兼容中文路径（PHP8 + Windows 10 19044 不需要手动对路径转码）
        // if ($this->config->isChineseWindows && !mb_detect_encoding($local_file, 'GB2312')) {$local_file = mb_convert_encoding($local_file, 'GB2312');}

        // --adjust-extension: 仅对既不是目录也没有扩展名的URL，根据content-type补全扩展名
        if (empty(get_ext(($local_file))) && $this->cfg['--adjust-extension']) {
            if (empty($http_info['content_type'])) {
                $http_info['content_type'] = 'text/html';
            }
            $content_type = $this->get_ext_by_content_type($http_info['content_type']);
            $local_file .= '.' . $content_type;
            $is_file_exist = $this->get_linktable_url_status($url . '.' . $content_type);
        }

        // 判断是否需要保存文件
        if ($is_file_exist && $this->cfg['--no-clobber']) {
            $this->echo_logs($number, date('Y-m-d H:i:s'), "{$url} -> {$local_file}", 'Exists, Skip save');
            return true;
        }
        // 写入文件
        if (!$this->writeContentToFile($local_file, $response, $url, $number)) {
            return false;
        }
        // 到这里表明本地文件存在
        $this->add_linktable_url_status($url, true);

        return true;
    }
    /**
     * 处理成功采集的任务
     * @param unknown $ch
     * @param unknown $response
     * @param unknown $http_info
     */
    public function success($ch, $response, $http_info): void
    {
        $number = $this->handleMap[$ch]['id'];
        // 获取原始URL
        $url = $this->handleMap[$ch]['url'];
        // 是否已存在
        $is_file_exist = $this->handleMap[$ch]['is_file_exist'];
        // 处理下载结果
        $this->handle_downloaded_content($url, $response, $http_info, $number, $is_file_exist);
    }
    public function error($ch, $message): void
    {
        $number = $this->handleMap[$ch]['id'];
        // 获取原始URL
        $url = $this->handleMap[$ch]['url'];
        // 无论响应是否空，标记后可跳过反复下载
        $this->add_linktable_url_status($url, false);
        $this->error_count++;

        $this->echo_logs($number, $this->handleMap[$ch]['url'], $message, "Error Counts: {$this->error_count}");
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
/**********
 * 获取扩展名
 */
function get_ext($file)
{
    // 移除查询参数
    if (strpos($file, '?') !== false) {
        $file = substr($file, 0, strpos($file, '?'));
    }

    // 移除锚点
    if (strpos($file, '#') !== false) {
        $file = substr($file, 0, strpos($file, '#'));
    }

    return pathinfo($file, PATHINFO_EXTENSION);
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
