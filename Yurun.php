<?php
/**
 * YurunPHP 开发框架 入口文件
 * @author Yurun <admin@yurunsoft.com>
 */
// 记录开始执行时间
define('YURUN_START', microtime(true));
if (PHP_VERSION < 5.3)
{
	exit('框架最低支持PHP 5.3版本，请尽量使用最新稳定版本，以确保更多功能以及更高运行效率！');
}
// 框架版本
define('YURUN_VERSION', '2.0.1');
// 版本声明，请勿去除或擅改，否则将在法律范围内不保证贵站能安全运行！
header('X-Powered-By:YurunPHP ' . YURUN_VERSION);
// 是否开启调试
defined('IS_DEBUG') or define('IS_DEBUG', true);
if (! IS_DEBUG)
{
	error_reporting(0);
}
// 框架根目录
define('ROOT_PATH', dirname(__FILE__) . '/');
// 框架核心目录
define('PATH_CORE', ROOT_PATH . 'Core/');
// 框架核心驱动目录
define('PATH_CORE_DRIVER', PATH_CORE . 'Driver/');
// 框架核心函数集目录
define('PATH_CORE_FUNCTIONS', PATH_CORE . 'Functions/');
// 框架配置目录
define('PATH_CONFIG', ROOT_PATH . 'Config/');
// 框架扩展目录
define('PATH_EX', ROOT_PATH . 'Ex/');
// 框架控制器类库目录
define('PATH_EX_CONTROL', PATH_EX . 'Control/');
// 框架模型类库目录
define('PATH_EX_MODEL', PATH_EX . 'Model/');
// 框架驱动类库目录
define('PATH_EX_DRIVER', PATH_EX . 'Driver/');
// 框架函数库目录
define('PATH_EX_FUNCTIONS', PATH_EX . 'Functions/');
// 框架扩展类库目录
define('PATH_EX_LIB', PATH_EX . 'Lib/');
// 语言目录
define('PATH_LANG', ROOT_PATH . 'Lang/');
// 框架模版目录
define('PATH_TEMPLATE', ROOT_PATH . 'Template/');
// 项目根目录
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
if (defined('IS_COMPILED'))
{
	// {compile}
	// 项目模版
	defined('APP_CONTROL') or define('APP_CONTROL', APP_PATH . Config::get('@.CONTROL_FOLDER') . '/');
	// 项目模版
	defined('APP_MODEL') or define('APP_MODEL', APP_PATH . Config::get('@.MODEL_FOLDER') . '/');
	// 项目模版
	defined('APP_TEMPLATE') or define('APP_TEMPLATE', APP_PATH . Config::get('@.TEMPLATE_FOLDER') . '/');
	// 项目类库
	defined('APP_LIB') or define('APP_LIB', APP_PATH . Config::get('@.LIB_FOLDER') . '/');
	// 项目类库
	defined('APP_LIB_EX') or define('APP_LIB_EX', APP_LIB . Config::get('@.LIB_EX_FOLDER') . '/');
	// 项目类库
	defined('APP_LIB_DRIVER') or define('APP_LIB_DRIVER', APP_LIB . Config::get('@.LIB_DRIVER_FOLDER') . '/');
	// 项目配置
	defined('APP_CONFIG') or define('APP_CONFIG', APP_PATH . Config::get('@.CONFIG_FOLDER') . '/');
	// 项目缓存
	defined('APP_CACHE') or define('APP_CACHE', APP_PATH . Config::get('@.CACHE_FOLDER') . '/');
	// 模版缓存
	defined('APP_CACHE_TEMPLATE') or define('APP_CACHE_TEMPLATE', APP_CACHE . Config::get('@.TEMPLATE_FOLDER') . '/');
	// 数据缓存
	defined('APP_CACHE_DATA') or define('APP_CACHE_DATA', APP_CACHE . Config::get('@.CACHE_DATA_FOLDER') . '/');
	// 页面缓存
	defined('APP_CACHE_PAGE') or define('APP_CACHE_PAGE', APP_CACHE . Config::get('@.CACHE_PAGE_FOLDER') . '/');
	// 项目模块目录
	defined('APP_MODULE') or define('APP_MODULE', APP_PATH . Config::get('@.MODULE_FOLDER') . '/');
	// 项目模块目录
	defined('APP_PLUGIN') or define('APP_PLUGIN', APP_PATH . Config::get('@.PLUGIN_FOLDER') . '/');
	// 项目语言目录
	defined('APP_LANG') or define('APP_LANG', APP_PATH . Config::get('@.LANG_FOLDER') . '/');
}
else
{
	// 临时核心配置
	$GLOBALS['cfg'] = include PATH_CONFIG . 'config.php';
	// 项目模版
	defined('APP_CONTROL') or define('APP_CONTROL', APP_PATH . $GLOBALS['cfg']['CONTROL_FOLDER'] . '/');
	// 项目模版
	defined('APP_MODEL') or define('APP_MODEL', APP_PATH . $GLOBALS['cfg']['MODEL_FOLDER'] . '/');
	// 项目模版
	defined('APP_TEMPLATE') or define('APP_TEMPLATE', APP_PATH . $GLOBALS['cfg']['TEMPLATE_FOLDER'] . '/');
	// 项目类库
	defined('APP_LIB') or define('APP_LIB', APP_PATH . $GLOBALS['cfg']['LIB_FOLDER'] . '/');
	// 项目类库
	defined('APP_LIB_EX') or define('APP_LIB_EX', APP_LIB . $GLOBALS['cfg']['LIB_EX_FOLDER'] . '/');
	// 项目类库
	defined('APP_LIB_DRIVER') or define('APP_LIB_DRIVER', APP_LIB . $GLOBALS['cfg']['LIB_DRIVER_FOLDER'] . '/');
	// 项目配置
	defined('APP_CONFIG') or define('APP_CONFIG', APP_PATH . $GLOBALS['cfg']['CONFIG_FOLDER'] . '/');
	// 项目缓存
	defined('APP_CACHE') or define('APP_CACHE', APP_PATH . $GLOBALS['cfg']['CACHE_FOLDER'] . '/');
	// 模版缓存
	defined('APP_CACHE_TEMPLATE') or define('APP_CACHE_TEMPLATE', APP_CACHE . $GLOBALS['cfg']['TEMPLATE_FOLDER'] . '/');
	// 数据缓存
	defined('APP_CACHE_DATA') or define('APP_CACHE_DATA', APP_CACHE . $GLOBALS['cfg']['CACHE_DATA_FOLDER'] . '/');
	// 页面缓存
	defined('APP_CACHE_PAGE') or define('APP_CACHE_PAGE', APP_CACHE . $GLOBALS['cfg']['CACHE_PAGE_FOLDER'] . '/');
	// 项目模块目录
	defined('APP_MODULE') or define('APP_MODULE', APP_PATH . $GLOBALS['cfg']['MODULE_FOLDER'] . '/');
	// 项目模块目录
	defined('APP_PLUGIN') or define('APP_PLUGIN', APP_PATH . $GLOBALS['cfg']['PLUGIN_FOLDER'] . '/');
	// 项目语言目录
	defined('APP_LANG') or define('APP_LANG', APP_PATH . $GLOBALS['cfg']['LANG_FOLDER'] . '/');
	if (defined('COMPILE'))
	{
		// 正在编译
		return;
	}
	else 
	{
		// 引用框架配置中规定的必须文件
		// 框架公用函数集
		foreach ($GLOBALS['cfg']['CORE_FUNCTIONS'] as $value)
		{
			require_once PATH_CORE_FUNCTIONS . "{$value}.php";
		}
		// 核心类
		foreach ($GLOBALS['cfg']['CORE_REQUIRE'] as $value)
		{
			require_once PATH_CORE . "{$value}.class.php";
		}
		// 核心库
		foreach ($GLOBALS['cfg']['CORE_DRIVER_REQUIRE'] as $value)
		{
			require_once PATH_CORE_DRIVER . "{$value}.class.php";
		}
	}
}
/**
 * 输出错误提示
 * @param mixed $err
 */
function printError($err)
{
	if(is_array($err))
	{// 错误数组
		$error=$err;
		ob_start();
		debug_print_backtrace();
		$error['trace']=ob_get_clean();
	}
	else
	{// 错误对象
		$error = array();
		$error['message']=$err->getMessage();
		$error['file']=$err->getFile();
		$error['line']=$err->getLine();
		$error['trace']=$err->getTraceAsString();
	}
	// 最后执行的sql语句
	if(isset($GLOBALS['debug']['lastsql']))
	{
		$error['lastsql']=$GLOBALS['debug']['lastsql'];
		unset($GLOBALS['debug']['lastsql']);
	}
	ob_end_clean();
	header('HTTP/1.1 500 Internal Server Error');
	header('Status:500 Internal Server Error');
	if(IS_DEBUG)
	{
		include Config::get('@.ERROR_DEBUG_TEMPLATE');
	}
	else
	{
		$url=Config::get('@.ERROR_URL');
		if(empty($url))
		{
			include Config::get('@.ERROR_RELEASE_TEMPLATE');
		}
		else
		{
			header("Location:{$url}");
		}
	}
	exit(str_repeat(' ', 1024));
}
register_shutdown_function(function(){
	if ($e = error_get_last())
	{
		switch($e['type']){
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				Log::add("错误:{$e['message']} 文件:{$e['file']} 行数:{$e['line']}");
				Log::save();
				ob_end_clean();
				printError($e);
				break;
		}
	}
	Log::save();
});
set_error_handler(function($errno, $errstr, $errfile, $errline){
	switch ($errno)
	{
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
			ob_end_clean();
			$error = array();
			$error['message']=$errstr;
			$error['file']=$errfile;
			$error['line']=$errline;
			if(Config::get('@.LOG_ON') && Config::get('@.LOG_ERROR'))
			{
				Log::add("错误:{$error['message']} 文件:{$error['file']} 行数:{$error['line']}");
			}
			printError($error);
			break;
	}
});
set_exception_handler(function($exception){
	if(Config::get('@.LOG_ON') && Config::get('@.LOG_ERROR'))
	{
		Log::add('错误:'.$exception->getMessage().' 文件:'.$exception->getFile().' 行数:'.$exception->getLine());
	}
	ob_end_clean();
	printError($exception);
});
// 载入项目配置
Config::create('App', 'php', APP_CONFIG . 'config.php');
// 根据调试和正式应用载入不同配置
Config::create('App_Run', 'php', APP_CONFIG . (IS_DEBUG ? 'debug.php' : 'release.php'));
// 载入插件列表
Config::create('Plugin', 'php', APP_CONFIG . 'plugin.php');
// 设置时区
date_default_timezone_set(Config::get('@.TIMEZONE'));
// 注册autoload方法，自动加载核心类
spl_autoload_register('yurunAutoload');
// 语言包支持
Lang::init();
// 插件扩展初始化
Event::init();
// 框架初始化完成事件
Event::trigger('YURUN_FRAMEWORK_LOAD_COMPLETE');
// 调度解析
Dispatch::resolve();
// 调度
Dispatch::exec();
function yurunAutoload($class)
{
	$file = "{$class}.class.php";
	// 使用配置类
	if (in_array($class, Config::get('@.CORE_CLASSES')))
	{
		// 核心
		require_once PATH_CORE . $file;
		return;
	}
	else if (in_array($class, Config::get('@.CORE_DRIVER_CLASSES')))
	{
		// 类库核心
		require_once PATH_CORE_DRIVER . "{$class}/{$file}";
		return;
	}
	$currModulePath = APP_MODULE . Dispatch::module() . '/';
	if ('Control' === substr($class, - 7))
	{
		// 控制器
		if (			// 其他扩展
		require_once_multi(array ($currModulePath . Config::get('@.CONTROL_FOLDER') . "/{$file}",			// 模块模型目录
		APP_CONTROL . $file,			// 项目控制器目录
		PATH_EX_CONTROL . "/{$file}"), 			// 框架控制器扩展目录
		false))
		{
			return;
		}
	}
	if ('Model' === substr($class, - 5))
	{
		// 模型
		if (			// 其他扩展
		require_once_multi(array ($currModulePath . Config::get('@.MODEL_FOLDER') . "/{$file}",			// 模块模型目录
		APP_MODEL . $file,			// 项目模型目录
		PATH_EX_MODEL . "/{$file}"), 			// 框架模型扩展目录
		false))
		{
			return;
		}
	}
	$file2 = '/' . getClassFirst($class) . "/{$file}";
	if (		// 其他扩展
	require_once_multi(array ($currModulePath . Config::get('@.LIB_FOLDER') . '/' . Config::get('@.LIB_DRIVER_FOLDER') . $file2,		// 模块类库驱动工厂类
	$currModulePath . Config::get('@.LIB_FOLDER') . '/' . Config::get('@.LIB_EX_FOLDER') . $file,		// 模块类库扩展
	APP_LIB_DRIVER . $file2,		// 项目类库驱动工厂类
	APP_LIB_EX . $file,		// 项目类库扩展目录
	PATH_EX_DRIVER . $file2,		// 框架扩展驱动工厂类
	PATH_EX_LIB . $file), 		// 框架扩展类库目录
	false))
	{
		return;
	}
}