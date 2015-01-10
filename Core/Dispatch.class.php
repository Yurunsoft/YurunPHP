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
		// 2014-12-02:新增多入口绑定模块控制器
		if(isset($GLOBALS['DEFAULT_MC']))
		{
			$dmc=explode('/',$GLOBALS['DEFAULT_MC']);
		}
		// 模块
		if (Config::get('@.MODULE_ON'))
		{
			self::$module = Request::get(Config::get('@.MODULE_NAME'), false);
			if (self::$module)
			{
				self::$module=ucfirst(self::$module);
			}
			else 
			{
				// 判断使用绑定模块还是默认模块
				if(isset($dmc[0]))
				{
					self::$module = $dmc[0];
				}
				else
				{
					self::$module = Config::get('@.MODULE_DEFAULT', '');
				}
			}
		}
		else
		{
			self::$module = '';
		}
		// 控制器
		self::$control = Request::get(Config::get('@.CONTROL_NAME'), false);
		if (self::$control)
		{
			self::$control=ucfirst(self::$control);
		}
		else 
		{
			// 判断使用绑定控制器还是默认控制器
			if(isset($dmc[1]))
			{
				self::$control = $dmc[1];
			}
			else
			{
				self::$control = Config::get('@.CONTROL_DEFAULT');
			}
		}
		// 动作
		self::$action = Request::get(Config::get('@.ACTION_NAME'), false);
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
		if (Config::get('@.MODULE_ON'))
		{
			// 载入模块配置
			Config::create('Module', 'php', APP_MODULE . self::$control .'/' .Config::get('@.CONFIG_FOLDER') . '/config.php');
		}
		// 控制器是否存在
		if (self::checkControl(self::$module,self::$control) && class_exists($class))
		{
			// 实例化控制器
			$yurunControl = new $class();
			$action = self::$action;
			if (is_callable(array($yurunControl, $action)))
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
				$domain = Config::get('@.DOMAIN');
			}
			if ($result === false)
			{
				$domain.='/'.basename($_SERVER['SCRIPT_NAME']);
				// 系统默认URL
				if ($module === '' || (isset($GLOBALS['HIDE_MODULE']) && $GLOBALS['HIDE_MODULE']))
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
				$tarr=explode(':', $val);
				if(count($tarr)>1)
				{
					if (strlen($tarr[1]) >= 2 && $tarr[1][0] === '\\' && $tarr[1][1] === 'R')
					{
						$k=$tarr[0];
						// 正则
						if (preg_match('/^' . substr(implode(':',$tarr), 2) . '$/', $param[$k]) <= 0)
						{
							$status = false;
							break;
						}
					}
					else
					{
						if(array_key_exists($tarr[0],$param))
						{
							$tarr[0]=$param[$tarr[0]];
							// Filter类
							if (! call_user_func_array('Validator::check',$tarr))
							{
								$status = false;
								break;
							}
						}
						else 
						{
							$status = false;
							break;
						}
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
	 * 判断是否有权限访问控制器
	 * $GLOBALS['DENY_CONTROLS']优先级大于$GLOBALS['ALLOW_CONTROLS']
	 * 精准定位模块控制器的优先级 大于 单独控制器名 大于 模块=>array(*)
	 * @param string $module
	 * @param string $control
	 * @return boolean
	 */
	public static function checkControl($module,$control)
	{
		// 判断具体模块中的控制器是否不允许访问
		if(isset($GLOBALS['DENY_CONTROLS']))
		{
			if(isset($GLOBALS['DENY_CONTROLS'][$module])
					&& in_array($control,$GLOBALS['DENY_CONTROLS'][$module])!==false)
			{
				return false;
			}
		}
		// 判断具体模块中的控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS']))
		{
			if(isset($GLOBALS['ALLOW_CONTROLS'][$module]) 
					&& in_array($control,$GLOBALS['ALLOW_CONTROLS'][$module])!==false)
			{
				return true;
			}
		}
		// 判断控制器是否不允许访问
		if(isset($GLOBALS['DENY_CONTROLS']))
		{
			if(in_array($control,$GLOBALS['DENY_CONTROLS'])!==false)
			{
				return false;
			}
		}
		// 判断控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS']))
		{
			if(in_array($control,$GLOBALS['ALLOW_CONTROLS'])!==false)
			{
				return true;
			}
		}
		// 判断模块下全部控制器是否不允许访问
		if(isset($GLOBALS['DENY_CONTROLS'][$module]))
		{
			if(in_array('*',$GLOBALS['DENY_CONTROLS'][$module])!==false)
			{
				return false;
			}
		}
		// 判断模块下全部控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS'][$module]))
		{
			if(in_array('*',$GLOBALS['ALLOW_CONTROLS'][$module])!==false)
			{
				return true;
			}
		}
		return isset($GLOBALS['DENY_CONTROLS']);
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