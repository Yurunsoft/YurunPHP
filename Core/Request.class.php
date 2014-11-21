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
		return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
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
		if ($compare === null)
		{
			// 返回请求方式
			return $_SERVER['REQUEST_METHOD'];
		}
		else
		{
			// 判断
			return strcmp($_SERVER['REQUEST_METHOD'], $compare) === 0;
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
		if ($referer === false)
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
		if($domain===false)
		{
			$domain=$_SERVER['HTTP_HOST'];
		}
		if($path==='' || $path[0]==='/')
		{
			return self::getProtocol()."{$domain}{$path}";
		}
		else
		{
			$dir=dirname(dirname($_SERVER['SCRIPT_NAME']));
			if($dir==='\\')
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
		switch (strtolower($arrName))
		{
			case 'get' :
				$data = $_GET;
				break;
			case 'post' :
				$data = $_POST;
				break;
			case 'cookie' :
				$data = $_COOKIE;
				break;
			case 'server' :
				$data = $_SERVER;
				break;
			case 'all' :
			default :
				$data = $_REQUEST;
				break;
		}
		return isset($data[$name]);
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
		switch (strtolower($arrName))
		{
			case 'get' :
				$data = $_GET;
				break;
			case 'post' :
				$data = $_POST;
				break;
			case 'cookie' :
				$data = $_COOKIE;
				break;
			case 'server' :
				$data = $_SERVER;
				break;
			case 'all' :
			default :
				$data = $_REQUEST;
				break;
		}
		if ($name === '')
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
		if ($filter === false)
		{
			// 不过滤直接返回
			return $value;
		}
		else
		{
			if (! (is_string($filter) || is_array($filter)))
			{
				// 按照配置中的过滤
				$filter = Config::get('@.DEFAULT_FILTER');
			}
			if ($filter === false)
			{
				// 不过滤直接返回
				return $value;
			}
			else
			{
				// 执行所有过滤操作
				return execFilter($value, $filter);
			}
		}
	}
	/**
	 * 获取访客IP
	 *
	 * @param bool $isHeader
	 *        	是否从请求头中判断，请求头可被伪造IP，所以不推荐使用，默认为false
	 * @return string
	 */
	public static function getIP($isHeader = false)
	{
		if ($isHeader)
		{
			if (isset($_SERVER['HTTP_CLIENT_IP']) && Validator::check_ip($_SERVER['HTTP_CLIENT_IP']))
			{
				return $_SERVER['HTTP_CLIENT_IP'];
			}
			else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && Validator::check_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		if (isset($_SERVER['REMOTE_ADDR']) && Validator::check_ip($_SERVER['REMOTE_ADDR']))
		{
			return $_SERVER['REMOTE_ADDR'];
		}
		else
		{
			return '';
		}
	}
}