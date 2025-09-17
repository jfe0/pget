@echo off
setlocal enabledelayedexpansion

REM 获取脚本所在目录的完整路径
set SCRIPT_PATH=%~dp0

REM 去掉末尾的反斜杠
set SCRIPT_PATH=%SCRIPT_PATH:~0,-1%

REM 将反斜杠替换为正斜杠
set SCRIPT_PATH=%SCRIPT_PATH:\=/%

REM 去除盘符后的冒号并替换为斜杠（修正版本）
for %%i in (A B C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    set SCRIPT_PATH=!SCRIPT_PATH:%%i:=/cygdrive/%%i!
)
echo %SCRIPT_PATH%
echo %date% %time%

REM 运行wget命令（wget不支持正则表达式某些高级语法，例如(?:...)）
wget.exe -nv -nc -r -p ^
-e robots=off ^
--adjust-extension ^
--output-file=wget.log ^
--no-check-certificate ^
--user-agent="Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)" ^
--reject-regex="\?|#|&|\.(rar|gz|zip|epub|txt|pdf|apk|deb|dmg|exe)$" ^
--wait=0.5 ^
--base="https://www.a.com/" ^
--referer="https://www.a.com/" ^
--span-hosts --domains="www.a.com" ^
https://www.a.com/
