<?php
/**
 * 缓存驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Cache extends Driver
{
	protected static $pageCacheName;
	/**
	 * 初始化
	 */
	public static function init()
	{
		parent::init();
		if (null === self::$pageCacheName)
		{
			self::$pageCacheName = urlencode("{$_SERVER['HTTP_HOST']}#{$_SERVER['REQUEST_URI']}") . serialize($_REQUEST);
		}
		self::create('file', null, Config::get('@.CACHE_FILE', array ()));
	}
	
	/**
	 * 添加缓存项
	 *
	 * @param string $type        	
	 * @param string $name        	
	 * @param array $data        	
	 * @return boolean
	 */
	public static function create($type = 'file', $name = null, $data = array())
	{
		if (null === $name)
		{
			// 当别名留空，默认使用首字母大写的缓存类型名称
			$name = ucfirst($type);
		}
		return ! self::exists($name) && false !== parent::create($type, $name, $data);
	}
	
	/**
	 * 设置缓存
	 *
	 * @param type $alias        	
	 * @param type $value        	
	 * @param array $config        	
	 * @param string $name
	 *        	缓存类型
	 * @return boolean
	 */
	public static function set($alias, $value = null, $config = array(), $name = 'File')
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->set($alias, $value, $config);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取缓存
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($alias, $default = false, $config = array(), $name = 'File')
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->get($alias, $default, $config);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 删除数据
	 *
	 * @param string $name        	
	 * @return boolean
	 */
	public static function remove($alias, $config = array(), $name = 'File')
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->remove($alias, $config);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 清空数据
	 */
	public static function clear($name = 'File')
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->clear();
		}
		else
		{
			return false;
		}
	}
	public static function cacheExists($alias, $config = array(), $name = 'File')
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->exists($alias, $config);
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
		return self::$pageCacheName;
	}
}
Cache::init();