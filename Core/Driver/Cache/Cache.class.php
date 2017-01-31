<?php
/**
 * 缓存驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Cache extends Driver
{
	/**
	 * 当前驱动名称
	 * @var type 
	 */
	public static $driverName = '';
	protected static $pageCacheName;
	
	protected static function __initBefore()
	{
		static::$driverName = 'Cache';
	}
	protected static function __onAppLoadBefore()
	{
		// 项目缓存文件目录
		defined('APP_CACHE') or define('APP_CACHE', APP_PATH . Config::get('@.CACHE_PATH') . DIRECTORY_SEPARATOR);
	}
	/**
	 * 设置缓存
	 *
	 * @param type $cacheName        	
	 * @param type $value        	
	 * @param array $option        	
	 * @param string $alias
	 *        	缓存类型
	 * @return boolean
	 */
	public static function set($cacheName, $value = null, $option = array(), $alias = null)
	{
		$obj = self::getInstance($alias);
		if ($obj)
		{
			return $obj->set($cacheName, $value, $option);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取缓存
	 *
	 * @param string $alias        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($cacheName, $default = false, $option = array(), $alias = null)
	{
		$obj = self::getInstance($alias);
		if ($obj)
		{
			return $obj->get($cacheName, $default, $option);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 删除数据
	 *
	 * @param string $alias        	
	 * @return boolean
	 */
	public static function remove($cacheName, $option = array(), $alias = null)
	{
		$obj = self::getInstance($alias);
		if ($obj)
		{
			return $obj->remove($cacheName, $option);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 清空数据
	 */
	public static function clear($alias = null)
	{
		$obj = self::getInstance($alias);
		if ($obj)
		{
			return $obj->clear();
		}
		else
		{
			return false;
		}
	}
	public static function cacheExists($cacheName, $option = array(), $alias = null)
	{
		$obj = self::getInstance($alias);
		if ($obj)
		{
			return $obj->exists($cacheName, $option);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取当前访问的缓存名
	 *
	 * @return type
	 */
	public static function pageCacheName()
	{
		if (null === self::$pageCacheName)
		{
			self::$pageCacheName = urlencode($_SERVER['HTTP_HOST'] . '#' . $_SERVER['REQUEST_URI']) . serialize($_REQUEST);
		}
		return self::$pageCacheName;
	}
}