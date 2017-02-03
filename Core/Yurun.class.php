<?php
/**
 * YurunPHP 开发框架 入口文件
 * @author Yurun <admin@yurunsoft.com>
 */
class Yurun
{
	/**
	 * 框架版本号
	 */
	const YURUN_VERSION = '2.0.0 Beta';
	/**
	 * 框架核心设置
	 * @var array 
	 */
	public static $config;
	/**
	 * 框架是否加载完成
	 * @var type 
	 */
	public static $isFrameworkLoaded = false;
	/**
	 * 项目是否加载完成
	 * @var type 
	 */
	public static $isAppLoaded = false;
	public static function exec()
	{
		// 框架版本声明
		header('X-Powered-By:YurunPHP ' . self::YURUN_VERSION);
		// 加载框架核心设置
		self::$config = include ROOT_PATH . 'Config/core.php';
		// 注册autoload方法，自动加载核心类
		spl_autoload_register('Yurun::autoload');
		// 加载函数集
		if(!IS_COMPILE)
		{
			require_once ROOT_PATH . 'Core' . DIRECTORY_SEPARATOR . 'functions.php';
		}
		// 站点本地根目录
		defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR);
		// 项目模块目录
		defined('APP_MODULE') or define('APP_MODULE', APP_PATH . Config::get('@.MODULE_PATH') . '/');
		// 项目配置目录
		defined('APP_CONFIG') or define('APP_CONFIG', APP_PATH . Config::get('@.CONFIG_PATH') . '/');
		// 项目类库目录
		defined('APP_LIB') or define('APP_LIB', APP_PATH . Config::get('@.LIB_PATH') . '/');
		// 框架模版目录
		defined('PATH_TEMPLATE') or define('PATH_TEMPLATE', ROOT_PATH . Config::get('@.TEMPLATE_PATH') . '/');
		// 项目模版目录
		defined('APP_TEMPLATE') or define('APP_TEMPLATE', APP_PATH . Config::get('@.TEMPLATE_PATH') . '/');
		// 错误异常处理
		register_shutdown_function('Yurun::onShutdown');
		set_error_handler('Yurun::onError');
		set_exception_handler('Yurun::onException');
		Lang::init();
		self::$isFrameworkLoaded = true;
		// 加载项目初始化处理文件
		$file = APP_LIB . 'init.php';
		include $file;
		// 项目开始加载事件
		Event::trigger('YURUN_APP_ONLOAD');
		// 设置时区
		date_default_timezone_set(Config::get('@.TIMEZONE'));
		// 静态文件目录
		$staticPath = Config::get('@.PATH_STATIC');
		define('LOCAL_STATIC_PATH', APP_PATH . Config::get('@.LOCAL_PATH_STATIC',$staticPath));
		$str = substr($staticPath,0,7);
		if('http://'!==$str && 'https:/'!==$str)
		{
			// 静态文件是网站根目录下的
			$staticPath = Request::getHome($staticPath);
		}
		// 静态文件目录
		define('STATIC_PATH',$staticPath);
		// 自动启动session
		if(Config::get('@.SESSION_AUTO_OPEN'))
		{
			Session::start();
		}
		self::$isAppLoaded = true;
		// 项目加载完成事件
		Event::trigger('YURUN_APP_LOAD_COMPLETE');
		// 初始化路由规则
		Dispatch::initRouteRules();
		Dispatch::resolve();
		// 释放变量
		unset($file,$str,$staticPath);
		Dispatch::exec();
	}
	public static function autoload($class)
	{
		static $currModulePath,$layerModulePath,$layerAppPath;
		$file = $class . '.class.php';
		$firstWord = getFirstWord($class);
		$lastWord = getLastWord($class);
		if(self::$isFrameworkLoaded)
		{
			if(null === $currModulePath)
			{
				// 当前模块路径
				$currModulePath = APP_MODULE . Dispatch::module() . DIRECTORY_SEPARATOR;
				$layerModulePath = defined('LAYER_MODULE_PATH') ? LAYER_MODULE_PATH : $currModulePath;
				$layerAppPath = defined('LAYER_APP_PATH') ? LAYER_APP_PATH : APP_PATH;
			}
			// 自定义分层加载支持
			$layers = Config::get('@.YURUN_LAYERS');
			foreach($layers as $layer)
			{
				if ($layer === $lastWord)
				{
					$filePath = $layer . DIRECTORY_SEPARATOR . $file;
					if (require_once_multi(array (
								$layerModulePath . $filePath,	// 模块分层目录
								$layerAppPath . $filePath,		// 项目分层目录
								ROOT_PATH . 'Ex/' . $filePath	// 框架分层目录
							),
							false))
					{
						return;
					}
				}
			}
			// 自动加载配置支持
			$rules = Config::get('@.AUTOLOAD_RULES');
			foreach($rules as $rule)
			{
				switch($rule['type'])
				{
					case 'FirstWord':
						if($firstWord === $rule['word'])
						{
							$filePath = parseAutoloadPath($rule['path'],$class,$rule['word']) . DIRECTORY_SEPARATOR . $file;
							if(require_once_multi(
								array (
									$currModulePath . $filePath,	// 模块目录
									APP_PATH . $filePath,			// 项目目录
									ROOT_PATH . $filePath			// 框架目录
								)
							))
							{
								return;
							}
						}
						break;
					case 'LastWord':
						if($lastWord === $rule['word'])
						{
							$filePath = parseAutoloadPath($rule['path'],$class,$rule['word']) . DIRECTORY_SEPARATOR . $file;
							if(require_once_multi(
								array (
									$currModulePath . $filePath,	// 模块目录
									APP_PATH . $filePath,			// 项目目录
									ROOT_PATH . $filePath			// 框架目录
								)
							))
							{
								return;
							}
						}
						break;
				}
			}
		}
		if(!IS_COMPILE)
		{
			if(in_array($firstWord, self::$config['CORE_DRIVER_CLASSES']))
			{
				include_once ROOT_PATH . 'Core/Driver/' . $firstWord . DIRECTORY_SEPARATOR . $file;
				return;
			}
			if(in_array($class, self::$config['CORE_CLASSES']))
			{
				include_once ROOT_PATH . 'Core' . DIRECTORY_SEPARATOR . $file;
				return;
			}
		}
		
		if(self::$isFrameworkLoaded)
		{
			require_once_multi(array(
				$currModulePath . 'Lib/' . $file,
				APP_PATH . 'Lib/' . $file,
				ROOT_PATH . 'Ex/Lib/' . $file
			));
		}
		else
		{
			require_once ROOT_PATH . 'Ex/Lib/' . $file;
		}
	}
	public static function onShutdown()
	{
		if ($e = error_get_last() && in_array($e['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR)))
		{
			if(Config::get('@.LOG_ERROR'))
			{
				Log::add("错误:{$e['message']} 文件:{$e['file']} 行数:{$e['line']}");
			}
			ob_end_clean();
			self::printError($e);
		}
		if(class_exists('Log',false))
		{
			Log::save();
		}
	}
	public static function onError($errno, $errstr, $errfile, $errline)
	{
		if(in_array($errno,array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR)))
		{
			ob_end_clean();
			$error = array(
				'message'	=>	$errstr,
				'file'		=>	$errfile,
				'line'		=>	$errline
			);
			if(Config::get('@.LOG_ERROR'))
			{
				Log::add("错误:{$error['message']} 文件:{$error['file']} 行数:{$error['line']}");
			}
			self::printError($error);
		}
	}
	public static function onException($exception)
	{
		if(Config::get('@.LOG_ERROR'))
		{
			Log::add('错误:'.$exception->getMessage().' 文件:'.$exception->getFile().' 行数:'.$exception->getLine());
		}
		ob_end_clean();
		self::printError($exception);
	}
	
	/**
	 * 输出错误提示
	 * @param mixed $err
	 */
	public function printError($err)
	{
		static $already;
		if(null !== $already)
		{
			return;
		}
		$already = true;
		if(is_array($err))
		{
			// 错误数组
			$error = $err;
			ob_start();
			debug_print_backtrace();
			$error['trace']=ob_get_clean();
		}
		else
		{
			// 错误对象
			$error = array(
				'message'	=>	$err->getMessage(),
				'file'		=>	$err->getFile(),
				'line'		=>	$err->getLine(),
				'trace'		=>	$err->getTraceAsString()
			);
		}
		// 最后执行的sql语句
		if(isset($GLOBALS['debug']['lastsql']))
		{
			$error['lastsql'] = $GLOBALS['debug']['lastsql'];
			unset($GLOBALS['debug']['lastsql']);
		}
		ob_end_clean();
		if(IS_CLI)
		{
			print_r($error);
			exit;
		}
		else
		{
			Response::msg('出现错误', Config::get('@.ERROR_URL',''), 500, $error);
		}
	}
}