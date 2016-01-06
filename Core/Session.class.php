<?php
/**
 * Session操作类
 * @author Yurun <admin@yurunsoft.com>
 */
class Session
{
	/**
	 * SESSION前缀
	 * @var string
	 */
	private static $prefix;
	/**
	 * 初始化Session
	 */
	public static function init()
	{
		self::name(Config::get('@.SESSION_NAME', null));
		self::savePath(Config::get('@.SESSION_SAVEPATH', null));
		self::useCookies(Config::get('@.SESSION_USE_COOKIES', null));
		self::cacheExpire(Config::get('@.SESSION_CACHE_EXPIRE', null));
		self::cacheLimiter(Config::get('@.SESSION_CACHE_LIMITER', null));
		self::gcProbability(Config::get('@.SESSION_GC_PROBABILITY', null));
		self::maxLifetime(Config::get('@.SESSION_MAX_LIFETIME', null));
		self::prefix(Config::get('@.SESSION_PREFIX', ''));
	}
	
	/**
	 * 开始Session
	 */
	public static function start()
	{
		session_start();
	}
	
	/**
	 * 暂停Session
	 */
	public static function pause()
	{
		session_write_close();
	}
	
	/**
	 * 停止Session
	 */
	public static function stop()
	{
		session_destroy();
		unset($_SESSION);
	}
	
	/**
	 * 设置Session值
	 *
	 * @param string $name        	
	 * @param mixed $value        	
	 */
	public static function set($name, $value)
	{
		$names = parseCfgName($name);
		$var = &$_SESSION;
		foreach($names as $name)
		{
			if('@' === $name)
			{
				$name = self::$prefix;
			}
			if('' !== $name)
			{
				$var = &$var[$name];
			}
		}
		$var = $value;
	}
	
	/**
	 * 获取Session值
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($name = null, $default = false)
	{
		if(null === $name)
		{
			return $_SESSION;
		}
		$names = parseCfgName($name);
		$var = &$_SESSION;
		foreach($names as $name)
		{
			if('@' === $name)
			{
				$name = self::$prefix;
			}
			if('' !== $name)
			{
				$var = &$var[$name];
			}
		}
		return isset($var) ? $var : $default;
	}
	
	/**
	 * 删除Session值
	 *
	 * @param string $name        	
	 */
	public static function delete($name)
	{
		$names = parseCfgName($name);
		$var = &$_SESSION;
		$lastName = array_pop($names);
		foreach($names as $name)
		{
			if('@' === $name)
			{
				$name = self::$prefix;
			}
			if('' !== $name)
			{
				$var = &$var[$name];
			}
		}
		unset($var[$lastName]);
		return true;
	}
	
	/**
	 * 清空所有Session
	 *
	 * @param string $name        	
	 */
	public static function clear()
	{
		$_SESSION = array ();
	}
	
	/**
	 * Session值是否存在
	 *
	 * @param string $name        	
	 */
	public static function exists($name)
	{
		if(null === $name)
		{
			return $_SESSION;
		}
		$names = parseCfgName($name);
		$var = &$_SESSION;
		foreach($names as $name)
		{
			if('@' === $name)
			{
				$name = self::$prefix;
			}
			if('' !== $name)
			{
				$var = &$var[$name];
			}
		}
		return isset($var);
	}
	
	/**
	 * Session会话名称
	 *
	 * @param string $name
	 *        	留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function name($name = null)
	{
		return null === $name ? session_name() : session_name($name);
	}
	
	/**
	 * Session保存路径
	 *
	 * @param string $savePath
	 *        	留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function savePath($savePath = null)
	{
		return null === $savePath ? session_save_path() : session_save_path($savePath);
	}
	
	/**
	 * Session使用Cookie
	 *
	 * @param string $use
	 *        	留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function useCookies($use = null)
	{
		return null === $use ? ini_get('session.use_cookies') : ini_set('session.use_cookies', $use);
	}
	
	/**
	 * 在客户端的缓存时间
	 *
	 * @param int $expire
	 *        	留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function cacheExpire($expire = null)
	{
		return null === $expire ? ini_get('session.cache_expire') : ini_set('session.cache_expire', $expire);
	}
	
	/**
	 * 在客户端的缓存方式
	 *
	 * @param string $limiter
	 *        	留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function cacheLimiter($limiter = null)
	{
		return null === $limiter ? ini_get('session.cache_limiter') : ini_set('session.cache_limiter', $limiter);
	}
	
	/**
	 * 每个请求触发session垃圾回收的概率
	 *
	 * @param float $probability
	 *        	取值范围：0.0-1.0
	 * @return mixed 值
	 */
	public static function gcProbability($probability = null)
	{
		if (null === $probability)
		{
			return ini_get('session.gc_probability') / ini_get('session.gc_divisor');
		}
		else
		{
			ini_set('session.gc_probability', 1);
			ini_set('session.gc_divisor', 1 / $probability);
			return $probability;
		}
	}
	
	/**
	 * session在服务端最长存储时间
	 *
	 * @param int $maxLifetime
	 *        	秒
	 * @return mixed 值
	 */
	public static function maxLifetime($maxLifetime = null)
	{
		return null === $maxLifetime ? ini_get('session.gc_maxlifetime') : ini_set('session.gc_maxlifetime', $$maxLifetime);
	}
	/**
	 * SESSION前缀
	 * @param string $prefix
	 * @return string
	 */
	public static function prefix($prefix = null)
	{
		if(null !== $prefix)
		{
			self::$prefix = $prefix;
		}
		return self::$prefix;
	}
}
Session::init();
Session::start();