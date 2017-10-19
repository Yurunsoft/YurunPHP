<?php
/**
 * Session操作类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class Session
{
	/**
	 * SESSION前缀
	 * @var string
	 */
	private static $prefix;
	/**
	 * 是否已经初始化过了
	 * @var bool
	 */
	private static $isInited = false;
	/**
	 * 对Session的更改
	 */
	private static $modifys = array();
	/**
	 * session id
	 * @var string
	 */
	public static $sessionID = null;
	/**
	 * 初始化
	 * @return bool 
	 */
	public static function init()
	{
		if(!self::$isInited)
		{
			self::name(Config::get('@.SESSION_NAME', null));
			self::savePath(Config::get('@.SESSION_SAVEPATH', null));
			self::useCookies(Config::get('@.SESSION_USE_COOKIES', null));
			self::cacheExpire(Config::get('@.SESSION_CACHE_EXPIRE', null));
			self::cacheLimiter(Config::get('@.SESSION_CACHE_LIMITER', null));
			self::gcProbability(Config::get('@.SESSION_GC_PROBABILITY', null));
			self::maxLifetime(Config::get('@.SESSION_MAX_LIFETIME', null));
			self::prefix(Config::get('@.SESSION_PREFIX', ''));
			self::cookieDomain(Config::get('@.SESSION_COOKIE_DOMAIN',null));
			$saveHandler = Config::get('@.SESSION_SAVE_HANDLER', 'files');
			self::saveHandler($saveHandler);
			if('user' === $saveHandler)
			{
				self::userSaveHandler(Config::get('@.SESSION_USER_SAVE_HANDLER'));
			}
			self::$isInited = true;
			if(null !== self::$sessionID)
			{
				session_id(self::$sessionID);
			}
			session_start();
			session_write_close();
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * 开始Session
	 */
	public static function start()
	{
		self::init();
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
	 * @param string $name        	
	 * @param mixed $value        	
	 */
	public static function set($name, $value)
	{
		self::$modifys[$name] = $value;
	}
	
	/**
	 * 获取Session值
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($name = null, $default = false)
	{
		if(isset(self::$modifys[$name]))
		{
			return self::$modifys[$name];
		}
		else if(array_key_exists($name,self::$modifys))
		{
			return $default;
		}
		else
		{
			self::init();
			self::save();
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
	}
	
	/**
	 * 删除Session值
	 * @param string $name        	
	 */
	public static function delete($name)
	{
		self::$modifys[$name] = null;
	}

	/**
	 * 获取一次值后将该值删除，可用于验证码等一次性功能
	 * @param string $name 
	 * @param mixed $default 
	 * @return mixed 
	 */
	public static function once($name, $default = false)
	{
		$value = self::get($name, $default);
		self::delete($name);
		return $value;
	}
	
	/**
	 * 清空所有Session
	 * @param string $name        	
	 */
	public static function clear()
	{
		self::start();
		$_SESSION = array ();
		session_write_close();
	}

	/**
	 * 保存所有Session更改
	 */
	public static function save()
	{
		if(empty(self::$modifys))
		{
			return;
		}
		self::start();
		foreach(self::$modifys as $name => $item)
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
			$var = $item;
		}
		self::$modifys = array();
		session_write_close();
	}
	
	/**
	 * Session值是否存在
	 * @param string $name        	
	 */
	public static function exists($name)
	{
		if(isset(self::$modifys[$name]))
		{
			return true;
		}
		else if(array_key_exists($name,self::$modifys))
		{
			return false;
		}
		else
		{
			self::init();
			self::save();
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
	}
	
	/**
	 * Session会话名称
	 * @param string $name 留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function name($name = null)
	{
		return null === $name ? session_name() : session_name($name);
	}
	
	/**
	 * Session保存路径
	 *
	 * @param string $savePath 留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function savePath($savePath = null)
	{
		return null === $savePath ? session_save_path() : session_save_path($savePath);
	}
	
	/**
	 * Session使用Cookie
	 * @param string $use 留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function useCookies($use = null)
	{
		return null === $use ? ini_get('session.use_cookies') : ini_set('session.use_cookies', $use);
	}
	
	/**
	 * 在客户端的缓存时间
	 * @param int $expire 留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function cacheExpire($expire = null)
	{
		return null === $expire ? ini_get('session.cache_expire') : ini_set('session.cache_expire', $expire);
	}
	
	/**
	 * 在客户端的缓存方式
	 * @param string $limiter 留空为取值
	 * @return mixed 值/修改前的值
	 */
	public static function cacheLimiter($limiter = null)
	{
		return null === $limiter ? ini_get('session.cache_limiter') : ini_set('session.cache_limiter', $limiter);
	}
	
	/**
	 * 每个请求触发session垃圾回收的概率
	 * @param float $probability 取值范围：0.0-1.0
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
	 * @param int $maxLifetime 秒
	 * @return mixed 值
	 */
	public static function maxLifetime($maxLifetime = null)
	{
		return null === $maxLifetime ? ini_get('session.gc_maxlifetime') : ini_set('session.gc_maxlifetime', $maxLifetime);
	}
	/**
	 * 设置session的cookie域名
	 * @param stirng $cookieDomain 
	 * @return string 
	 */
	public static function cookieDomain($cookieDomain = null)
	{
		return null === $cookieDomain ? ini_get('session.cookie_domain') : ini_set('session.cookie_domain', $cookieDomain);
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
	/**
	 * Session的save_handler
	 * @param type $saveHandler
	 */
	public static function saveHandler($saveHandler = null)
	{
		return null === $saveHandler ? ini_get('session.save_handler') : ini_set('session.save_handler', $saveHandler);
	}
	/**
	 * 设置用户自定义Session存储方式的处理类
	 * @param type $className
	 */
	public static function userSaveHandler($userSaveHandler)
	{
		if(PHP_VERSION >= 5.4)
		{
			return session_set_save_handler(new $userSaveHandler,true);
		}
		else
		{
			register_shutdown_function('session_write_close');
			$handler = new $userSaveHandler;
			return session_set_save_handler(
					array($handler,'open'),
					array($handler,'close'),
					array($handler,'read'),
					array($handler,'write'),
					array($handler,'destroy'),
					array($handler,'gc')
			,true);
		}
	}
}