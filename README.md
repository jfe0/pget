# Pget2
Pget2：PHP 模拟 wget + 并发

 * 
 * A PHP implementation of Wget + multi catcher, object-oriented refactored version. Only surports HTTP,HTTPS,FTP
 * no cache except link table
 * 
 * Usage examples:
 * php pget2.php --max-threads=5 --recursive --wait=0.5 --reject="woff,jpg,png,webp" --accept="html,js,css" --reject-regex="\?|(?:\/down\/)" https://domain/
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
 * 
 * 一个 PHP 版的 Wget + 并发。仅下载 HTTP,HTTPS,FTP 文件
 * 链接表分片
 * 
 * 用法示例：
 * php pget.php --recursive --adjust-extension --restrict-file-names --no-check-certificate --tries=10 --wait=0.5 --save-cookies="cookie" --user-agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0" --reject-regex="\?|#|&|(?:\.rar)|(?:\.zip)|(?:\.epub)|(?:\.txt)|(?:\.pdf)" --reject="woff,jpg,png,webp" --accept="html,js,css"  https://domain/
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
