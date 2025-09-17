@echo off

set "script_path=%~dp0"
echo %script_path%
echo %date% %time%
@REM ::使用dos变量时不能带引号，但是其它参数的值字符串必须带引号。因为dos的传参方式问题导致。双引号内管道符不生效
::runhiddenconsole.exe ^ 
php.exe C:\workspace\crawler\pget2.php --directory-prefix=%script_path% --store-database --page-requisites --recursive --no-verbose --no-echo --no-check-certificate --user-agent="Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)" --reject-regex="\?|#|&|\.(?:rar|gz|zip|epub|txt|pdf|apk|deb|dmg|exe)$" --tries=6 --max-threads=2 --wait=1 --pause-time=200 --pause-period=30 --strip-ss --strip-blank --sub-string="<div class=\"container mt-md-2\">|<footer>" --delete-string="<div class=\"d-none\">|<footer>" --span-hosts --domains="5000yan.com" https://a.com/
