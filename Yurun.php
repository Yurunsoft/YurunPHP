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
	 * 框架的分层
	 * @var array
	 */
	public static $layers = array();
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
	/**
	 * 初始化框架
	 */
	public static function init()
	{
		// 记录开始执行时间
		define('YURUN_START', microtime(true));
		// 框架版本声明
		header('X-Powered-By:YurunPHP ' . self::YURUN_VERSION);
		// 判断PHP版本
		if (PHP_VERSION < 5.3)
		{
			exit('运行YurunPHP框架需要最低PHP 5.3版本！');
		}
		// 是否命令行CLI模式下运行
		define('IS_CLI', 'cli' === PHP_SAPI);
		// 是否开启调试
		defined('IS_DEBUG') or define('IS_DEBUG', true);
		// 框架根目录
		define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
		// 加载框架核心设置
		self::$config = include ROOT_PATH . 'Config/core.php';
		// 注册autoload方法，自动加载核心类
		spl_autoload_register('Yurun::autoload');
		// 加载函数集
		$functionPath = ROOT_PATH . 'Core' . DIRECTORY_SEPARATOR . 'Functions' . DIRECTORY_SEPARATOR;
		foreach(self::$config['CORE_FUNCTIONS'] as $item)
		{
			require_once $functionPath . $item . '.php';
		}
		// 站点本地根目录
		defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR);
		self::initConsts();
		// 错误异常处理
		register_shutdown_function('Yurun::onShutdown');
		set_error_handler('Yurun::onError');
		set_exception_handler('Yurun::onException');
		Lang::init();
		self::$isFrameworkLoaded = true;
	}
	public static function exec()
	{
		// 项目加载事件
		Event::trigger('YURUN_APP_ONLOAD');
		// 设置时区
		date_default_timezone_set(Config::get('@.TIMEZONE'));
		self::parseStatic();
		self::$isAppLoaded = true;
		// 项目加载事件
		Event::trigger('YURUN_APP_LOAD_COMPLETE');
		Dispatch::resolve();
		Dispatch::exec();
	}
	private static function parseStatic()
	{
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
		unset($staticPath);
	}
	private static function initConsts()
	{
		// 项目模块目录
		defined('APP_MODULE') or define('APP_MODULE', APP_PATH . Config::get('@.MODULE_PATH') . '/');
		// 项目类库目录
		defined('APP_LIB') or define('APP_LIB', APP_PATH . Config::get('@.LIB_PATH') . '/');
		// 框架模版目录
		defined('PATH_TEMPLATE') or define('PATH_TEMPLATE', ROOT_PATH . Config::get('@.TEMPLATE_PATH') . '/');
		// 项目模版目录
		defined('APP_TEMPLATE') or define('APP_TEMPLATE', APP_PATH . Config::get('@.TEMPLATE_PATH') . '/');
	}
	public static function autoload($class)
	{
		$file = $class . '.class.php';
		if(self::$isFrameworkLoaded)
		{
			$currModulePath = APP_MODULE . Dispatch::module() . '/';
			// 自定义分层支持
			$layers = Config::get('@.YURUN_LAYERS');
			$layerModulePath = defined('LAYER_MODULE_PATH') ? LAYER_MODULE_PATH : $currModulePath;
			$layerAppPath = defined('LAYER_APP_PATH') ? LAYER_APP_PATH : APP_PATH;
			foreach($layers as $layer)
			{
				if ($layer === substr($class, - strlen($layer)))
				{
					$filePath = $layer . '/' . $file;
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
		}
		
		$classFirst = getClassFirst($class);
		$index = array_search($classFirst, self::$config['CORE_DRIVER_CLASSES']);
		if(false !== $index)
		{
			include_once ROOT_PATH . 'Core/Driver/' . $classFirst . '/' . $file;
			if($classFirst === $class)
			{
				$classFirst::init();
			}
			return;
		}
		if(in_array($class, self::$config['CORE_CLASSES']))
		{
			include_once ROOT_PATH . 'Core' . DIRECTORY_SEPARATOR . $file;
			return;
		}
	}
	public static function onShutdown()
	{
		if ($e = error_get_last())
		{
			if(in_array($e['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR)))
			{
				if(Config::get('@.LOG_ERROR'))
				{
					Log::add("错误:{$e['message']} 文件:{$e['file']} 行数:{$e['line']}");
				}
				ob_end_clean();
				self::printError($e);
			}
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
Yurun::init();
Yurun::exec();