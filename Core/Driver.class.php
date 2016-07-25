<?php
/**
 * 驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Driver
{
	// 配置数组
	protected static $configs = array ();
	// 实例数组
	protected static $instance = array ();
	public static function init()
	{
		$driver = get_called_class();
		$className = $driver . 'Base';
		if (! class_exists($className, false))
		{
			// 类名
			$fileName = "{$driver}/{$className}.class.php";
			if (! class_exists($className, false))
			{
				// 载入该驱动基类
				require_once_multi(array (APP_MODULE . Dispatch::module() . '/' . Config::get('@.LIB_FOLDER') . '/' . Config::get('@.LIB_DRIVER_FOLDER') . $fileName,				// 模块驱动目录
				APP_LIB_DRIVER . $fileName,				// 项目目录
				PATH_CORE_DRIVER . $fileName), 				// 框架驱动目录
				false);
			}
		}
	}
	/**
	 * 创建驱动实例
	 *
	 * @param string $name        	
	 * @param string $alias        	
	 * @param array $args        	
	 * @return mixed
	 */
	public static function create($name, $alias = '')
	{
		$name = ucfirst($name);
		$driver = get_called_class();
		// 类名
		$className = $driver . $name;
		// 是否存在该类实例
		if (isset(self::$instance[$driver][$alias]))
		{
			return self::$instance[$driver][$alias];
		}
		else
		{
			// 驱动路径
			$fileName = "{$driver}/{$className}.class.php";
			// 引入驱动文件
			if (class_exists($className, false) || require_once_multi(array (APP_MODULE . Dispatch::module() . '/' . Config::get('@.LIB_FOLDER') . '/' . Config::get('@.LIB_DRIVER_FOLDER') . '/' . $fileName,			// 模块驱动目录
			APP_LIB_DRIVER . $fileName,			// 项目目录
			PATH_CORE_DRIVER . $fileName), 			// 框架驱动目录
			false))
			{
				// 实例化
				$ref = new ReflectionClass($className);
				$args = array_slice(func_get_args(), 2);
				self::$instance[$driver][$alias] = $ref->newInstanceArgs($args);
				self::$configs[$driver][] = $className;
			}
			else
			{
				// 引入驱动失败
				return false;
			}
			// 返回实例
			return self::$instance[$driver][$alias];
		}
	}
	/**
	 * 获得驱动实例，不存在返回null
	 *
	 * @param type $name        	
	 * @return mixed
	 */
	public static function getObj($name='')
	{
		static $driver;
		// 第一次获取当前驱动名
		if (null === $driver)
		{
			$driver = get_called_class();
		}
		if (isset(self::$instance[$driver][$name]))
		{
			return self::$instance[$driver][$name];
		}
		else
		{
			return null;
		}
	}
	public static function exists($name)
	{
		static $driver;
		// 第一次获取当前驱动名
		if (null === $driver)
		{
			$driver = get_called_class();
		}
		return isset(self::$instance[$driver][$name]);
	}
	
	/**
	 * 获取驱动实例数量
	 *
	 * @return int
	 */
	public static function length($name)
	{
		return count(self::$configs[$name]);
	}
}