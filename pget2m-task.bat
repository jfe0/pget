@echo off

set "script_path=%~dp0"
echo %script_path%
echo %date% %time%
@REM ::使用dos变量时不能带引号，但是其它参数的值字符串必须带引号。因为dos的传参方式问题导致。双引号内管道符不生效
::runhiddenconsole.exe ^ 
php.exe C:\workspace\crawler\pget2m.php --directory-prefix=%script_path% --store-database --page-requisites --recursive --no-verbose --no-clobber --adjust-extension --no-check-certificate --output-file="pget2.log" --save-cookies="cookie" --user-agent="Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)" --reject-regex="\?|#|&|\.(?:rar|gz|zip|epub|txt|pdf|apk|deb|dmg|exe)$|\/users\/" --tries=100 --max-threads=10 --wait=2 --pause-time=2000 --pause-tries=1000 --sub-string="<div class=\"panel-body\">|<div class=\"panel-heading title\">" http://www.ccbbp.com/
