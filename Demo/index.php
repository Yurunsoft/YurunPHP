<?php
// 是否开启调试模式。部署请设为false，可以提升性能。
define('IS_DEBUG', true);
// 系统根目录
define('APP_PATH',__DIR__.DIRECTORY_SEPARATOR);
// 引入YurunPHP框架入口文件
if(IS_DEBUG)
{
	require_once '../Yurun.php';
}
else
{
	require_once '../Yurun-min.php';
}