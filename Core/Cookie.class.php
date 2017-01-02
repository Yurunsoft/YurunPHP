<?php
/**
 * Cookie操作类
 * @author Yurun <admin@yurunsoft.com>
 */
class Cookie
{
	// cookie的服务器路径,默认为/
	public static $path;
	// 有效域名，默认为当前域名
	public static $domain;
	// 规定是否通过安全的 HTTPS 连接来传输cookie
	public static $secure;
	
	/**
	 * 初始化Cookie配置
	 */
	public static function init()
	{
		self::$path = Config::get('@.COOKIE_PATH', '/');
		self::$domain = Config::get('@.COOKIE_DOMAIN', '');
		self::$secure = Config::get('@.COOKIE_SECURE', 0);
	}
	
	/**
	 * 设置cookie值
	 *
	 * @param string $name        	
	 * @param mixed $value        	
	 * @param int $expire        	
	 * @param string $path        	
	 * @param string $domain        	
	 * @param int $secure
	 *        	HTTPS 连接来传输cookie
	 * @return boolean
	 */
	public static function set($name, $value, $expire = 0, $path = '', $domain = '', $secure = '')
	{
		return setcookie($name, $value, is_numeric($expire) ? $expire : self::$expire, '' == $path ? self::$path : $path, '' == $domain ? self::$domain : $domain, '' == $secure ? self::$secure : $secure);
	}
	
	/**
	 * 获取cookie值
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($name, $default = false)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
	}

	/**
	 * cookie值是否存在
	 *
	 * @param string $name
	 */
	public static function exists($name)
	{
		return isset($_COOKIE[$name]);
	}

	/**
	 * 获取cookie值并过滤
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function getF($name, $filter = array(), $default = false)
	{
		if (isset($_COOKIE[$name]))
		{
			return execFilter($_COOKIE[$name], $filter);
		}
		else
		{
			return $default;
		}
	}
	
	/**
	 * 删除cookie
	 *
	 * @param string $name        	
	 * @param string $path        	
	 * @param string $domain        	
	 * @param int $secure        	
	 * @return type
	 */
	public static function delete($name,$path = '', $domain = '', $secure = '')
	{
		if(!is_array($name))
		{
			$name=func_get_args();
		}
		foreach ($name as $val)
		{
			setcookie($val,null,0,'' == $path ? self::$path : $path, '' == $domain ? self::$domain : $domain, '' == $secure ? self::$secure : $secure);
		}
		return true;
	}
	
	/**
	 * 删除所有cookie
	 *
	 * @param string $path        	
	 * @param string $domain        	
	 * @param int $secure        	
	 */
	public static function clear($path = '', $domain = '', $secure = '')
	{
		foreach ($_COOKIE as $key => $v)
		{
			self::delete($key, $path, $domain, $secure);
		}
	}
}
Cookie::init();