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
	public static function switchMCA($rule)
	{
		if (! empty($rule))
		{
			$arr = explode('/', $rule, 3);
			$s = count($arr);
			if(1 === $s)
			{
				self::$action = $arr[0];
			}
			else if(2 === $s)
			{
				self::$control = ucfirst($arr[0]);
				self::$action = $arr[1];
			}
			else if(3 === $s)
			{
				self::$module = ucfirst($arr[0]);
				self::$control = ucfirst($arr[1]);
				self::$action = $arr[2];
			}
		}
	}
	/**
	 * 调度
	 *
	 * @param string $rule        	
	 * @throws Exception
	 */
	public static function exec($rule = null, $pageNotFound = true)
	{
		self::switchMCA($rule);
		if (Config::get('@.MODULE_ON'))
		{
			// 载入模块配置
			Config::create('Module', 'php', APP_MODULE . self::$module .'/' .Config::get('@.CONFIG_FOLDER') . '/config.php');
		}
		if(false===self::call() && $pageNotFound)
		{
			// 页面不存在
			Response::msg(Lang::get('PAGE_NOT_FOUND'), null, 404);
		}
	}
	/**
	 * 调用
	 * @return boolean
	 */
	public static function call()
	{
		$class = self::$control . 'Control';
		// 控制器是否存在
		if (self::checkControl(self::$module,self::$control) && class_exists($class))
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
				$action = "_R_{$action}";
				if (method_exists($yurunControl, $action))
				{
					$yurunControl->$action();
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			return false;
		}
		return true;
	}
	/**
	 * 生成URL
	 *
	 * @param string $rule        	
	 * @param array $param        	
	 * @param mixed $subDomain 子域名前缀
	 * @return type
	 */
	public static function url($rule = null, $param = array(), $subDomain = null)
	{
		// 插件
		$args=array('rule'=>$rule, 'param'=>$param, 'subDomain'=>$subDomain, 'result'=>'');
		Event::trigger('YP_URL_CREATE',$args);
		if (!empty($args['result']))
		{
			return $args['result'];
		}
		else
		{
			// 解析url
			$urlInfo=parse_url($rule);
			// url规则查询参数处理
			if(!empty($urlInfo['query']))
			{
				parse_str($urlInfo['query'], $tmpParam);
				$param=array_merge($tmpParam,$param);
			}
			// 模块名、控制器名和动作名
			if (empty($urlInfo['path']))
			{
				$module = self::$module;
				$control = self::$control;
				$action = self::$action;
			}
			else
			{
				$arr = explode('/', $urlInfo['path'], 3);
				$s = count($arr);
				if(1 === $s)
				{
					$module = self::$module;
					$control = self::$control;
					$action = $arr[0];
				}
				else if(2 === $s)
				{
					$module = self::$module;
					$control = ucfirst($arr[0]);
					$action = $arr[1];
				}
				else if(3 === $s)
				{
					$module = ucfirst($arr[0]);
					$control = ucfirst($arr[1]);
					$action = $arr[2];
				}
			}
			// 提供给URL规则使用的参数
			if(!isset($param[Config::get('@.MODULE_NAME')]))
			{
				$param[Config::get('@.MODULE_NAME')]=$module;
			}
			if(!isset($param[Config::get('@.CONTROL_NAME')]))
			{
				$param[Config::get('@.CONTROL_NAME')]=$control;
			}
			if(!isset($param[Config::get('@.ACTION_NAME')]))
			{
				$param[Config::get('@.ACTION_NAME')]=$action;
			}
			// 根据是否有模块取不同的配置
			if ('' === $module)
			{
				$cfgName = array($control,$action);
			}
			else
			{
				$cfgName = array($module,$control,$action);
			}
			// 检测是否有自定义URL
			$result = self::checkRule($cfgName, $param);
			// 域名
			if(isset($urlInfo['host']))
			{
				$domain=$urlInfo['host'];
			}
			else 
			{
				$domain = Config::get('@.DOMAIN');
				if(empty($domain))
				{
					$domain=$_SERVER['HTTP_HOST'].strtr(dirname($_SERVER['SCRIPT_NAME']), '\\','/');
				}
			}
			// 子域名
			if(!empty($subDomain))
			{
				$domain="{$subDomain}.{$domain}";
			}
			// 去除域名后尾的/
			if('/'===substr($domain,-1,1))
			{
				$domain=substr($domain,0,-1);
			}
			// 协议，http、https……
			if(isset($urlInfo['scheme']))
			{
				$protocol=$urlInfo['scheme'];
			}
			else 
			{
				$protocol = Config::get('@.URL_PROTOCOL');
				if(empty($protocol))
				{
					$protocol=Request::getProtocol();
				}
			}
			$url="{$protocol}{$domain}/";
			if(!$result['hidefile'])
			{
				if(''===$result['filename'])
				{
					$url.=basename($_SERVER['SCRIPT_NAME']);
				}
				else 
				{
					$url.=$result['filename'];
				}
			}
			if ('' === $module)
			{
				$param = array_merge(array (Config::get('@.CONTROL_NAME') => $control,Config::get('@.ACTION_NAME') => $action), $param);
			}
			else
			{
				$param = array_merge(array (Config::get('@.MODULE_NAME') => $module,Config::get('@.CONTROL_NAME') => $control,Config::get('@.ACTION_NAME') => $action), $param);
				if($result['hidemodule'] || (Config::get('@.URL_HIDE_DEFAULT_MODULE') && $module===Config::get('@.MODULE_DEFAULT')))
				{
					unset($param[Config::get('@.MODULE_NAME')]);
				}
			}
			if($result['hidecontrol'] || (Config::get('@.URL_HIDE_DEFAULT_CONTROL') && $control===Config::get('@.CONTROL_DEFAULT')))
			{
				unset($param[Config::get('@.CONTROL_NAME')]);
			}
			if($result['hideaction'] || (Config::get('@.URL_HIDE_DEFAULT_ACTION') && $action===Config::get('@.ACTION_DEFAULT')))
			{
				unset($param[Config::get('@.ACTION_NAME')]);
			}
			if ($result['result'])
			{
				// 自定义URL，替换变量
				$s = preg_match_all('/{([^}]+)}/', $result['rule'], $r);
				for ($i = 0; $i < $s; ++ $i)
				{
					if(isset($param[$r[1][$i]]))
					{
						$result['rule'] = str_replace($r[0][$i], urlencode($param[$r[1][$i]]), $result['rule']);
						unset($param[$r[1][$i]]);
					}
					else if('#query#'!==$r[1][$i])
					{
						$result['rule'] = str_replace($r[0][$i], '', $result['rule']);
					}
				}
				if(false!==stripos($result['rule'],'{#query#}'))
				{
					$query=http_build_query($param);
					if(''!==$query)
					{
						if(false===stripos($result['rule'],'?'))
						{
							$query="?{$query}";
						}
						else
						{
							$query="&{$query}";
						}
					}
					$result['rule'] = str_replace('{#query#}', $query, $result['rule']);
				}
				$url.=$result['rule'];
			}
			else if(!empty($param))
			{
				$url.='?'.http_build_query($param);
			}
			// 锚点支持
			if(!empty($urlInfo['fragment']))
			{
				$url.="#{$urlInfo['fragment']}";
			}
			return $url;
		}
	}
	/**
	 * 检测是否有自定义URL
	 *
	 * @param string $rules        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function checkRule($rule, $param)
	{
		$result=array('result'=>false);
		static $outRules=array('filename','hidefile','hideaction','hidecontrol','hidemodule');
		$continue=true;
		while($continue)
		{
			if(0 == count($rule))
			{
				$continue = false;
			}
			$strRule = implode('.',$rule);
			$rules=Config::get('@.URL_RULE'.(''==$strRule?'':".{$strRule}"));
			array_pop($rule);
			if(is_array($rules))
			{
				foreach($outRules as $ruleItem)
				{
					if(!isset($result[$ruleItem]))
					{
						if(isset($rules["-{$ruleItem}"]))
						{
							$result[$ruleItem]=$rules["-{$ruleItem}"];
						}
					}
				}
			}
			else
			{
				continue;
			}
			if($result['result'])
			{
				$isAllIsset=true;
				foreach($outRules as $ruleItem)
				{
					if(!isset($result[$ruleItem]))
					{
						$isAllIsset=false;
						break;
					}
				}
				if($isAllIsset)
				{
					break;
				}
				else 
				{
					continue;
				}
			}
			foreach ($rules as $key => $value)
			{
				if(is_array($value) || false!==in_array(substr($key,1),$outRules))
				{
					continue;
				}
				$result['result']=true;
				$arr = preg_split('/\s/', $value);
				if (1 === count($arr) && '' === $arr[0])
				{
					break;
				}
				foreach ($arr as $val)
				{
					$tarr=explode(':', $val);
					if(count($tarr)>1)
					{
						if (isset($tarr[1][1]) && '\\' === $tarr[1][0] && 'R' === $tarr[1][1])
						{
							$k=$tarr[0];
							// 正则
							if (preg_match('/^' . substr($tarr[1], 2) . '$/', $param[$k]) <= 0)
							{
								$result['result']=false;
								break;
							}
						}
						else
						{
							if(isset($param[$tarr[0]]))
							{
								$tarr[0]=$param[$tarr[0]];
								// Filter类
								if (! call_user_func_array('Validator::check',$tarr))
								{
									$result['result']=false;
									break;
								}
							}
							else
							{
								$result['result']=false;
								break;
							}
						}
					}
				}
				if($result['result'])
				{
					break;
				}
			}
		}
		if(!isset($result['filename']))
		{
			$result['filename']='';
		}
		if(!isset($result['hidefile']))
		{
			$result['hidefile']=false;
		}
		if(!isset($result['hideaction']))
		{
			$result['hideaction']=false;
		}
		if(!isset($result['hidecontrol']))
		{
			$result['hidecontrol']=false;
		}
		if(!isset($result['hidemodule']))
		{
			$result['hidemodule']=false;
		}
		if($result['result'])
		{
			$result['rule']=$key;
		}
		else 
		{
			$result['rule']='';
		}
		return $result;
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
					&& false!==in_array($control,$GLOBALS['DENY_CONTROLS'][$module]))
			{
				return false;
			}
		}
		// 判断具体模块中的控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS']))
		{
			if(isset($GLOBALS['ALLOW_CONTROLS'][$module]) 
					&& false!==in_array($control,$GLOBALS['ALLOW_CONTROLS'][$module]))
			{
				return true;
			}
		}
		// 判断控制器是否不允许访问
		if(isset($GLOBALS['DENY_CONTROLS']))
		{
			if(false!==in_array($control,$GLOBALS['DENY_CONTROLS']))
			{
				return false;
			}
		}
		// 判断控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS']))
		{
			if(false!==in_array($control,$GLOBALS['ALLOW_CONTROLS']))
			{
				return true;
			}
		}
		// 判断模块下全部控制器是否不允许访问
		if(isset($GLOBALS['DENY_CONTROLS'][$module]))
		{
			if(false!==in_array('*',$GLOBALS['DENY_CONTROLS'][$module]))
			{
				return false;
			}
		}
		// 判断模块下全部控制器是否允许访问
		if(isset($GLOBALS['ALLOW_CONTROLS'][$module]))
		{
			if(false!==in_array('*',$GLOBALS['ALLOW_CONTROLS'][$module]))
			{
				return true;
			}
		}
		if($GLOBALS['ALLOW_CONTROLS'])
		{
			return isset($GLOBALS['DENY_CONTROLS']);
		}
		else 
		{
			return true;
		}
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