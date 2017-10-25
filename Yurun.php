<?php
/**
 * YurunPHP 开发框架 入口文件
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
if(!defined('IS_COMPILE') && is_file(__DIR__ . '/Yurun-min.php'))
{
	require_once __DIR__ . '/Yurun-min.php';
}
else
{
	// 记录开始执行时间
	define('YURUN_START', microtime(true));
	// 判断PHP版本
	if (PHP_VERSION < 5.4)
	{
		exit('运行YurunPHP框架需要最低PHP 5.4版本！');
	}
	// 是否编译模式
	defined('IS_COMPILE') or define('IS_COMPILE',false);
	// 是否命令行CLI模式下运行
	define('IS_CLI', 'cli' === PHP_SAPI);
	// 是否开启调试
	defined('IS_DEBUG') or define('IS_DEBUG', true);
	// 框架根目录
	define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
	if(!IS_COMPILE)
	{
		require_once ROOT_PATH . 'Core/Yurun.class.php';
	}
	Yurun::exec();
}