<?php
/**
 * 请求获取类
 * @author Yurun <admin@yurunsoft.com>
 */
class Request
{
	/**
	 * 判断是否为https方式访问
	 *
	 * @return boolean
	 */
	public static function isHttps()
	{
		return isset($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']);
	}
	/**
	 * 获取当前协议，http://或https://
	 *
	 * @return string
	 */
	public static function getProtocol()
	{
		return 'http' . (self::isHttps() ? 's' : '') . '://';
	}
	/**
	 * 获取或判断当前请求方式
	 *
	 * @param string $compare
	 *        	比较的请求方式
	 * @return mixed
	 */
	public static function method($compare = null)
	{
		if (null === $compare)
		{
			// 返回请求方式
			return $_SERVER['REQUEST_METHOD'];
		}
		else
		{
			// 判断
			return 0 === strcasecmp($_SERVER['REQUEST_METHOD'], $compare);
		}
	}
	/**
	 * 获取当前来路页面
	 *
	 * @param boolean $emptyDomain        	
	 * @return string
	 */
	public static function referer($emptyDomain = false)
	{
		// 获取来路
		$referer = self::server('HTTP_REFERER');
		// 判断是否有来路
		if (false === $referer)
		{
			if ($emptyDomain)
			{
				// 返回当前站点首页
				return self::getHome();
			}
			else
			{
				return '';
			}
		}
		else
		{
			return $referer;
		}
	}
	/**
	 * 获取站点地址
	 *
	 * @param string $path        	
	 * @return string
	 */
	public static function getHome($path = '')
	{
		$domain=Config::get('@.DOMAIN');
		if(false===$domain)
		{
			$domain=$_SERVER['HTTP_HOST'];
		}
		if(''===$path || '/'===$path[0])
		{
			return self::getProtocol().$domain.$path;
		}
		else
		{
			$dir=dirname($_SERVER['SCRIPT_NAME']);
			if('\\'===$dir)
			{
				$dir='';
			}
			return self::getProtocol()."{$domain}{$dir}/{$path}";
		}
	}
	/**
	 * 魔术方法
	 *
	 * @param type $name        	
	 * @param type $arguments        	
	 * @return type
	 */
	public static function __callStatic($name, $arguments)
	{
		return call_user_func_array('self::getAll', array_merge(array ($name), $arguments));
	}
	public static function exists($arrName, $name)
	{
		$arrName = strtolower($arrName);
		if('get' === $arrName)
		{
			return isset($_GET[$name]);
		}
		else if('post' === $arrName)
		{
			return isset($_POST[$name]);
		}
		else if('cookie' === $arrName)
		{
			return isset($_COOKIE[$name]);
		}
		else if('server' === $arrName)
		{
			return isset($_SERVER[$name]);
		}
		else 
		{
			return isset($_REQUEST[$name]);
		}
	}
	/**
	 * 获取超全局变量值
	 *
	 * @param string $arrName        	
	 * @param string $name        	
	 * @param mixed $default        	
	 * @param mixed $filter        	
	 * @return mixed
	 */
	public static function getAll($arrName, $name = '', $default = false, $filter = false)
	{
		$arrName = strtolower($arrName);
		if('get' === $arrName)
		{
			$data = &$_GET;
		}
		else if('post' === $arrName)
		{
			$data = &$_POST;
		}
		else if('cookie' === $arrName)
		{
			$data = &$_COOKIE;
		}
		else if('server' === $arrName)
		{
			$data = &$_SERVER;
		}
		else
		{
			$data = &$_REQUEST;
		}
		if ('' === $name)
		{
			// 全部的值
			$value = $data;
		}
		// 判断指定的值是否存在
		else if (isset($data[$name]))
		{
			$value = $data[$name];
		}
		else
		{
			// 返回默认值
			return $default;
		}
		if (empty($filter))
		{
			// 不过滤直接返回
			return $value;
		}
		else if (false!==$filter)
		{
			// 按照配置中的过滤
			$filter = Config::get('@.DEFAULT_FILTER');
			// 执行所有过滤操作
			return execFilter($value, $filter);
		}
		else
		{
			return $value;
		}
	}
	/**
	 * 获取访客IP
	 * @param bool $isHeader 是否从请求头中判断，请求头可被伪造IP，所以不推荐使用，默认为false
	 * @param mixed $default 获取IP失败后返回的默认值，默认是0.0.0.0
	 * @return string
	 */
	public static function getIP($isHeader=false,$default='0.0.0.0')
	{
		if($isHeader)
		{
			if (isset($_SERVER['HTTP_CLIENT_IP']) && Validator::ip($_SERVER['HTTP_CLIENT_IP']))
			{
				return $_SERVER['HTTP_CLIENT_IP'];
			}
			else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && Validator::ip($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		if (isset($_SERVER['REMOTE_ADDR']) && Validator::ip($_SERVER['REMOTE_ADDR']))
		{
			return $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			return $default;
		}
	}
}