## Pget2 PHP 爬虫脚本介绍

### 功能概述

`Pget2` 是一个功能强大的 PHP 并发爬虫脚本，专为网站镜像和批量网页采集任务而设计。它支持 HTTP/HTTPS/FTP 协议，具备递归下载、内容过滤、进度日志记录等特性，适用于大规模网页数据采集和网站备份。

### 主要特点

1. **并发下载支持** - 支持多线程并发下载，提高采集效率
2. **递归爬取功能** - 可递归下载页面内所有链接，支持深度控制
3. **内容处理能力** - 提供内容截取、字符串过滤、标签处理等功能
4. **智能文件管理** - 自动创建目录结构，支持文件扩展名自动补全
5. **断点续传机制** - 支持中断后继续下载，避免重复工作
6. **灵活的过滤系统** - 支持正则表达式和文件后缀过滤
7. **多格式存储** - 支持保存为文件或存入数据库
8. **跨平台兼容** - 兼容 Windows 和 Linux 系统

### 核心参数分类

#### 基础参数
- `url` - 请求的目标 URL
- `--mirror` - 镜像整个网站
- `--input-file` - 包含 URL 列表的文件路径

#### 文件处理参数
- `--no-clobber` - 不覆盖已存在的本地文件
- `--directory-prefix` - 文件保存目录
- `--adjust-extension` - 自动补全 HTML 文件扩展名
- `--restrict-file-names` - 转义非 ASCII 和特殊字符

#### 过滤参数
- `--reject` - 拒绝的文件后缀列表
- `--accept` - 接受的文件后缀列表
- `--accept-regex` - 接受 URL 的正则表达式
- `--reject-regex` - 拒绝 URL 的正则表达式

#### 内容处理参数
- `--sub-string` - 内容截取规则
- `--delete-string` - 删除指定字符串
- `--strip-ss` - 去除 SCRIPT 和 STYLE 标签
- `--strip-tags` - 去除网页标签
- `--strip-blank` - 清理空白行和空格

#### 递归爬取参数
- `--recursive` - 启用递归下载
- `--page-requisites` - 下载页面依赖资源
- `--no-parent` - 限制不向上级目录爬取
- `--span-hosts` - 允许跨主机爬取
- `--level` - 设置递归深度

#### 网络请求参数
- `--wait` - 设置请求间隔时间
- `--tries` - 设置重试次数
- `--save-cookies/--load-cookies` - Cookie 管理
- `--remote-encoding/--local-encoding` - 编码设置

#### 暂停控制参数
- `--pause-tries` - 最大暂停次数
- `--pause-period` - 暂停周期间隔
- `--pause-time` - 单次暂停时长

该脚本适用于需要批量下载网页内容、创建网站镜像或进行网络数据采集的场景。

## Pget2 PHP Crawler Script Introduction

### Function Overview

`Pget2` is a powerful PHP concurrent crawler script designed for website mirroring and bulk web page collection tasks. It supports HTTP/HTTPS/FTP protocols and features recursive downloading, content filtering, and progress logging, making it suitable for large-scale web data collection and website backup.

### Main Features

1. **Concurrent Download Support** - Supports multi-threaded concurrent downloads to improve collection efficiency
2. **Recursive Crawling** - Can recursively download all links on a page with depth control
3. **Content Processing Capabilities** - Provides content extraction, string filtering, and tag processing functions
4. **Smart File Management** - Automatically creates directory structures and supports automatic file extension completion
5. **Resume Capability** - Supports resuming after interruption to avoid duplicate work
6. **Flexible Filtering System** - Supports regular expression and file extension filtering
7. **Multi-format Storage** - Supports saving as files or storing in databases
8. **Cross-platform Compatibility** - Compatible with both Windows and Linux systems

### Core Parameter Categories

#### Basic Parameters
- `url` - Target URL for the request
- `--mirror` - Mirror an entire website
- `--input-file` - File path containing a list of URLs

#### File Processing Parameters
- `--no-clobber` - Do not overwrite existing local files
- `--directory-prefix` - Directory for saving files
- `--adjust-extension` - Automatically complete HTML file extensions
- `--restrict-file-names` - Escape non-ASCII and special characters

#### Filtering Parameters
- `--reject` - List of rejected file extensions
- `--accept` - List of accepted file extensions
- `--accept-regex` - Regular expression for accepted URLs
- `--reject-regex` - Regular expression for rejected URLs

#### Content Processing Parameters
- `--sub-string` - Content extraction rules
- `--delete-string` - Delete specified strings
- `--strip-ss` - Remove SCRIPT and STYLE tags
- `--strip-tags` - Remove web page tags
- `--strip-blank` - Clean up blank lines and spaces

#### Recursive Crawling Parameters
- `--recursive` - Enable recursive downloading
- `--page-requisites` - Download page dependencies (images, CSS, JS)
- `--no-parent` - Restrict crawling from ascending to parent directories
- `--span-hosts` - Allow cross-host crawling
- `--level` - Set recursive depth

#### Network Request Parameters
- `--wait` - Set interval between requests
- `--tries` - Set retry attempts
- `--save-cookies/--load-cookies` - Cookie management
- `--remote-encoding/--local-encoding` - Encoding settings

#### Pause Control Parameters
- `--pause-tries` - Maximum pause attempts
- `--pause-period` - Minimum interval between periodic pauses
- `--pause-time` - Duration of each pause

This script is suitable for scenarios requiring bulk web content downloading, website mirroring, or network data collection.
