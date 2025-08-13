@echo off

set "script_path=%~dp0"
echo %script_path%
echo %date% %time%
::使用dos变量时不能待引号，但是其它参数的值字符串必须带引号。因为dos的传参方式问题导致
::runhiddenconsole.exe ^ 
php.exe C:\workspace\wwwcrawler\pget2m.php --directory-prefix=%script_path% --store-database --recursive --no-verbose --no-clobber --adjust-extension --no-check-certificate --output-file="pget2.log" --save-cookies="cookie" --user-agent="Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)" --reject-regex="\?|#|&|(\.rar)|(\.zip)|(\.epub)|(\.txt)|(\.pdf)" --tries=100 --max-threads=10 --wait=2 --pause-time=2000 --pause-tries=5 http://www.tdbat.com/
