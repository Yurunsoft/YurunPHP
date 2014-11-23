<?php
/**
 * 调度类
 * @author Yurun <admin@yurunsoft.com>
 */
class Dispatch
{
	// 模块名
	protected static $module = '';
	// 控制器名
	protected static $control = '';
	// 动作名
	protected static $action = '';
	/**
	 * 解析
	 */
	public static function resolve()
	{
		// 模块
		if (Config::get('@.MODULE_ON'))
		{
			self::$module = ucfirst(Request::get(Config::get('@.MODULE_NAME'), false));
			if (! self::$module)
			{
				self::$module = Config::get('@.MODULE_DEFAULT', '');
			}
		}
		else
		{
			self::$module = '';
		}
		// 控制器
		self::$control = ucfirst(Request::get(Config::get('@.CONTROL_NAME'), false));
		if (! self::$control)
		{
			self::$control = Config::get('@.CONTROL_DEFAULT');
		}
		// 动作
		self::$action = ucfirst(Request::get(Config::get('@.ACTION_NAME'), false));
		if (! self::$action)
		{
			self::$action = Config::get('@.ACTION_DEFAULT');
		}
	}
	/**
	 * 调度
	 *
	 * @param string $rule        	
	 * @throws Exception
	 */
	public static function exec($rule = null)
	{
		if (! empty($rule))
		{
			$arr = explode('/', $rule, 3);
			switch (count($arr))
			{
				case 1 :
					self::$action = $arr[0];
					break;
				case 2 :
					self::$control = ucfirst($arr[0]);
					self::$action = $arr[1];
					break;
				case 3 :
					self::$module = ucfirst($arr[0]);
					self::$control = ucfirst($arr[1]);
					self::$action = $arr[2];
					break;
			}
		}
		$class = self::$control . 'Control';
		// 控制器是否存在
		if (class_exists($class))
		{
			// 实例化控制器
			$yurunControl = new $class();
			$action = self::$action;
			if (method_exists($yurunControl, $action))
			{
				$yurunControl->$action();
			}
			else
			{
				Response::msg(Lang::get('PAGE_NOT_FOUND'), null, 404);
			}
		}
		else
		{
			// 控制器不存在
			Response::msg(Lang::get('PAGE_NOT_FOUND'), null, 404);
		}
		exit();
	}
	/**
	 * 生成URL
	 *
	 * @param string $rule        	
	 * @param array $param        	
	 * @param string $domain        	
	 * @param boolean $subDomain
	 *        	$domain是否作为子域名前缀
	 * @return type
	 */
	public static function url($rule = null, $param = array(), $domain = null, $subDomain = false)
	{
		// 插件
		$eventValue = Event::trigger('COMBILE_URL', false, $rule, $param, $domain, $subDomain);
		if (! empty($eventValue))
		{
			foreach ($eventValue as $value)
			{
				if (! empty($value))
				{
					return $value;
				}
			}
		}
		else
		{
			// 模块名、控制器名和动作名
			if (empty($rule))
			{
				$module = self::$module;
				$control = self::$control;
				$action = self::$action;
			}
			else
			{
				$arr = explode('/', $rule, 3);
				switch (count($arr))
				{
					case 1 :
						$module = self::$module;
						$control = self::$control;
						$action = $arr[0];
						break;
					case 2 :
						$module = self::$module;
						$control = ucfirst($arr[0]);
						$action = $arr[1];
						break;
					case 3 :
						$module = ucfirst($arr[0]);
						$control = ucfirst($arr[1]);
						$action = $arr[2];
						break;
				}
			}
			// 根据是否有模块取不同的配置
			if ($module === '')
			{
				$cfgName = "@.URL_RULE.{$control}.{$action}";
			}
			else
			{
				$cfgName = "@.URL_RULE.{$module}.{$control}.{$action}";
			}
			// 检测是否有自定义URL
			$result = self::checkRule(Config::get($cfgName), $param);
			if (empty($domain))
			{
				$domain = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
			}
			if ($result === false)
			{
				// 系统默认URL
				if ($module === '')
				{
					return Request::getProtocol() . "{$domain}?" . http_build_query(array_merge(array (Config::get('@.CONTROL_NAME') => $control,Config::get('@.ACTION_NAME') => $action), $param));
				}
				else
				{
					return Request::getProtocol() . "{$domain}?" . http_build_query(array_merge(array (Config::get('@.MODULE_NAME') => $module,Config::get('@.CONTROL_NAME') => $control,Config::get('@.ACTION_NAME') => $action), $param));
				}
			}
			else
			{
				if ($module === '')
				{
					$param = array_merge(array ('c' => $control,'a' => $action), $param);
				}
				else
				{
					$param = array_merge(array ('m' => $module,'c' => $control,'a' => $action), $param);
				}
				// 自定义URL，替换变量
				$s = preg_match_all('/{([^}]+)}/', $result, $r);
				for ($i = 0; $i < $s; ++ $i)
				{
					$result = str_replace($r[0][$i], isset($param[$r[1][$i]]) ? urlencode($param[$r[1][$i]]) : '', $result);
				}
				return Request::getProtocol() . "{$domain}/{$result}";
			}
		}
	}
	/**
	 * 检测是否有自定义URL
	 *
	 * @param string $rules        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function checkRule($rules, $param)
	{
		if(!is_array($rules))return false;
		foreach ($rules as $key => $value)
		{
			$arr = preg_split('/\s/', $value);
			if (count($arr) === 1 && $arr[0] === '')
			{
				return $key;
			}
			$status = true;
			foreach ($arr as $val)
			{
				list ($k, $v) = explode(':', $val);
				if (strlen($v) >= 2 && $v[0] === '\\' && $v[1] === 'R')
				{
					// 正则
					if (preg_match('/^' . substr($v, 2) . '$/', $param[$k]) <= 0)
					{
						$status = false;
						break;
					}
				}
				else
				{
					// Filter类
					if (! Validator::check($param[$k], $v))
					{
						$status = false;
						break;
					}
				}
			}
			if ($status)
			{
				return $key;
			}
		}
		return false;
	}
	/**
	 * 获取模块名
	 *
	 * @return string
	 */
	public static function module()
	{
		return self::$module;
	}
	/**
	 * 获取控制器名
	 *
	 * @return string
	 */
	public static function control()
	{
		return self::$control;
	}
	/**
	 * 获取动作名
	 *
	 * @return string
	 */
	public static function action()
	{
		return self::$action;
	}
}