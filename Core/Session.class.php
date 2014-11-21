<?php
/**
 * Session操作类
 * @author Yurun <admin@yurunsoft.com>
 */
class Session
{
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
		$_SESSION[$name] = $value;
	}
	
	/**
	 * 获取Session值
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($name, $default = false)
	{
		return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}
	
	/**
	 * 删除Session值
	 *
	 * @param string $name        	
	 */
	public static function delete($name)
	{
		if(!is_array($name))
		{
			$name=func_get_args();
		}
		foreach ($name as $val)
		{
			unset($_SESSION[$val]);
		}
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
		return isset($_SESSION[$name]);
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
		return is_null($name) ? session_name() : session_name($name);
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
		return is_null($savePath) ? session_save_path() : session_save_path($savePath);
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
		return is_null($use) ? ini_get('session.use_cookies') : ini_set('session.use_cookies', $use);
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
		return is_null($expire) ? ini_get('session.cache_expire') : ini_set('session.cache_expire', $expire);
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
		return is_null($limiter) ? ini_get('session.cache_limiter') : ini_set('session.cache_limiter', $limiter);
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
		if (is_null($probability))
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
		return is_null($maxLifetime) ? ini_get('session.gc_maxlifetime') : ini_set('session.gc_maxlifetime', $$maxLifetime);
	}
}
Session::init();
Session::start();