<?php
/**
 * 调度类
 * @author Yurun <admin@yurunsoft.com>
 */
class Dispatch
{
	// 模块名
	private static $module = '';
	// 控制器名
	private static $control = '';
	// 动作名
	private static $action = '';
	// 给action传的参数
	private static $data = array();
	// 处理后的路由规则
	private static $routeRules = array();
	// 当前文件的配置
	private static $currFileCfg;
	// 权限判断结果
	private static $checkAuth;
	// 当前访问的文件名
	private static $currFileName;
	/**
	 * 解析
	 */
	public static function resolve()
	{
		// 路由解析
		self::parseRoute();
		// 模块
		if (Config::get('@.MODULE_ON'))
		{
			if(empty(self::$module))
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
		}
		else
		{
			self::$module = '';
		}
		// 控制器
		if(empty(self::$control))
		{
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
		}
		// 动作
		if(empty(self::$action))
		{
			self::$action = Request::get(Config::get('@.ACTION_NAME'), false);
			if (! self::$action)
			{
				self::$action = Config::get('@.ACTION_DEFAULT');
			}
		}
		if((null === self::$checkAuth && !self::checkAuth()) || false === self::$checkAuth)
		{
			// 没有权限访问
			Response::msg(Lang::get('PAGE_AUTH_NOT'), null, 500);
		}
	}
	/**
	 * 初始化处理路由规则
	 * @return string
	 */
	public static function initRouteRules()
	{
		Config::create('app_route', 'php', APP_CONFIG . 'route.php');
		self::$routeRules = array();
		$rules = Config::get('@.route.rules');
		foreach($rules as $rule => $url)
		{
			$fields = array();
			// 文件名支持
			if(false === strpos($rule,'@'))
			{
				$fileName = '';
			}
			else
			{
				list($fileName,$rule) = explode('@',$rule,2);
			}
			self::$routeRules[$rule] = array('rule' => addcslashes(preg_replace_callback(
					'/\[([^\]]+)\]/i',
					function($matches)use(&$fields){
						if(false !== strpos($matches[1],':'))
						{
							try {
								list($name,$type,$lengthStart,$lengthEnd) = explode(':',$matches[1]);
							} catch (Exception $e) {
							}
						}
						else
						{
							$name = &$matches[1];
							$type = null;
							$lengthStart = null;
							$lengthEnd = null;
						}
						$fields[] = array('name' => $name,'type' => $type,'lengthStart'=>$lengthStart,'lengthEnd'=>$lengthEnd);
						return '(' . convertToRegexType($type,$lengthStart,$lengthEnd) . ')';
					},
					$rule,
					-1),'/'),'url' => $url, 'fields' => $fields, 'filename' => $fileName);
		}
		// 当前访问的文件名
		self::$currFileName = basename($_SERVER['SCRIPT_FILENAME']);
		$currFileNameCfgFormat = str_replace('.','\.',self::$currFileName);
		self::$currFileCfg = Config::get('@.route.entrance.'.$currFileNameCfgFormat);
	}
	/**
	 * 处理pathinfo模式的url
	 * @return string|unknown
	 */
	private static function parseRoute()
	{
		if(Config::get('@.PATHINFO_ON') && isset($_SERVER['PATH_INFO'])) // PATHINFO
		{
			$requestURI = $_SERVER['PATH_INFO'];
		}
		else if(Config::get('@.URL_PARSE_ON')) // URL路由解析
		{
			list($requestURI) = explode('&',$_SERVER['QUERY_STRING']);
			if(false !== strpos($requestURI,'='))
			{
				$requestURI = '';
			}
			if(false === strpos($requestURI,'.'))
			{
				$trequestURI = $requestURI;
			}
			else
			{
				$trequestURI = str_replace('.','_',$requestURI);
			}
			if(isset($_GET[$trequestURI],$_REQUEST[$trequestURI]))
			{
				unset($_GET[$trequestURI],$_REQUEST[$trequestURI]);
			}
			unset($trequestURI);
		}
		if('' == $requestURI && Config::get('@.QUERY_PATHINFO_ON')) // 参数传入URL路由解析
		{
			$requestURI = Request::get(Config::get('@.PATHINFO_QUERY_NAME'),'');
		}
		// 防止前面带/
		if(isset($requestURI[0]) && '/' === $requestURI[0])
		{
			$requestURI = substr($requestURI,1);
		}
		// 防止后面带/
		if('/' === substr($requestURI,-1))
		{
			$requestURI = substr($requestURI,0,-1);
		}
		foreach(self::$routeRules as $rule => $cfg)
		{
			if(($cfg['filename'] === self::$currFileName || '' === $cfg['filename']) && preg_match('/^' . $cfg['rule'] . '$/i',$requestURI,$matches)>0)
			{
				$url = preg_replace_callback(
						'/\$(\d+)/i',
						function($matches2) use($matches){
							return $matches[$matches2[1]];
						},
						$cfg['url'],
						-1);
				$mca = explode('/',$url);
				// 模块控制器动作获取
				if(isset($mca[2])) // 格式完整
				{
					self::$module = ucfirst($mca[0]);
					self::$control = ucfirst($mca[1]);
					self::$action = $mca[2];
				}
				else
				{
					throw new Exception('rules 规则格式错误！');
				}
				// 访问权限判断
				if(self::checkAuth())
				{
					self::$checkAuth = true;
					$s = count($cfg['fields']);
					self::$data = array();
					for($i=0;$i<$s;++$i)
					{
						self::$data[$cfg['fields'][$i]['name']] = $matches[$i+1];
					}
					$_GET = array_merge($_GET,self::$data);
					$_REQUEST = array_merge($_REQUEST,self::$data);
					return;
				}
			}
		}
		// 模块控制器动作获取
		if(!isset($mca) && '' !== $requestURI)
		{
			$mca = explode('/',$requestURI);
		}
		if(isset($mca[2])) // 3个成员
		{
			self::$module = ucfirst($mca[0]);
			self::$control = ucfirst($mca[1]);
			self::$action = $mca[2];
		}
		else
		{
			if(isset($mca[1])) // 2个成员
			{
				self::$control = ucfirst($mca[0]);
				self::$action = $mca[1];
				if(isset(self::$currFileCfg['default_mca']))
				{
					list(self::$module) = explode('/',self::$currFileCfg['default_mca']);
				}
			}
			else if(isset($mca[0])) // 1个成员
			{
				self::$action = $mca[0];
				if(isset(self::$currFileCfg['default_mca']))
				{
					list(self::$module,self::$control) = explode('/',self::$currFileCfg['default_mca']);
				}
			}
			else
			{
				if(isset(self::$currFileCfg['default_mca']))
				{
					list(self::$module,self::$control,self::$action) = explode('/',self::$currFileCfg['default_mca']);
				}
			}
		}
	}
	public static function checkAuth()
	{
		if(null != self::$currFileCfg)
		{
			if('deny' === self::$currFileCfg['priority'])
			{
				if(isset(self::$currFileCfg['deny']))
				{
					if(self::checkDeny(self::$currFileCfg['deny']))
					{
						return false;
					}
				}
				return true;
			}
			else if('allow' === self::$currFileCfg['priority'] || empty(self::$currFileCfg['priority']))
			{
				if(isset(self::$currFileCfg['allow']))
				{
					if(self::checkAllow(self::$currFileCfg['allow']))
					{
						return true;
					}
				}
				return false;
			}
		}
		return true;
	}
	/**
	 * 检查允许规则。允许返回true，否则返回false
	 * @param unknown $rule
	 * @throws Exception
	 * @return boolean
	 */
	private static function checkAllow($rule)
	{
		foreach($rule as $item)
		{
			$mca = explode('/',$item);
			if(!isset($mca[2]))
			{
				throw new Exception('allow 规则' . $item . '格式错误！');
			}
			if($mca[0] !== self::$module && $mca[0] !== '*')
			{
				continue;
			}
			if($mca[1] !== self::$control && $mca[1] !== '*')
			{
				continue;
			}
			if($mca[2] === self::$action || $mca[2] === '*')
			{
				return true;
			}
		}
		return false;
	}
	/**
	 * 检查拒绝规则。拒绝返回true，否则返回false
	 * @param unknown $rule
	 * @throws Exception
	 * @return boolean
	 */
	private static function checkDeny($rule)
	{
		foreach($cfg['deny'] as $item)
		{
			$mca = explode('/',$item);
			if(!isset($mca[2]))
			{
				throw new Exception('deny 规则' . $item . '格式错误！');
			}
			if($mca[0] === self::$module || $mca[0] === '*')
			{
				return true;
			}
			if($mca[1] === self::$control || $mca[1] === '*')
			{
				return true;
			}
			if($mca[2] === self::$action || $mca[2] === '*')
			{
				return true;
			}
		}
		return false;
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
		if (class_exists($class))
		{
			// 实例化控制器
			$yurunControl = new $class();
			$action = self::$action;
			if (method_exists($yurunControl, $action))
			{
				call_user_func_array(array(&$yurunControl,$action),self::$data);
			}
			else
			{
				$action = '_R_' . $action;
				if (method_exists($yurunControl, $action))
				{
					call_user_func_array(array(&$yurunControl,$action),self::$data);
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
	public static function url($rule = null, $param = null, $subDomain = null)
	{
		// 支持数组和文本两种数据格式
		if(empty($param))
		{
			$param = array();
		}
		else if(is_string($param))
		{
			parse_str($param, $param);
		}
		// 事件处理
		$args=array('rule'=>$rule, 'param'=>$param, 'subDomain'=>$subDomain, 'result'=>'');
		Event::trigger('YP_URL_CREATE',$args);
		if (!empty($args['result']))
		{
			return $args['result']; // 返回事件处理结果
		}
		else
		{
			// 解析url
			$urlInfo = parse_url($rule);
			// 处理参数
			if(!empty($urlInfo['query']))
			{
				parse_str($urlInfo['query'], $tmpParam);
				$param = array_merge($tmpParam,$param);
				unset($tmpParam);
			}
			// 处理path
			try {
				$mca = explode('/', $urlInfo['path']);
			} catch (Exception $e) {
			}
			if(isset($mca[2])) // 3个成员
			{
				$module = ucfirst($mca[0]);
				$control = ucfirst($mca[1]);
				$action = $mca[2];
			}
			else if(isset($mca[1])) // 2个成员
			{
				$module = self::$module;
				$control = ucfirst($mca[0]);
				$action = $mca[1];
			}
			else if(isset($mca[0]) && '' !== $mca[0]) // 1个成员
			{
				$module = self::$module;
				$control = self::$control;
				$action = $mca[0];
			}
			else // 为空时
			{
				$module = self::$module;
				$control = self::$control;
				$action = self::$action;
			}
			unset($mca);
			$path = "{$module}/{$control}/{$action}";
			// 根据路由规则判断
			$urlPath = self::parseUrlRoute($path,$param,$filename);
			if(false === $urlPath)
			{
				// 文件名
				$filename = Config::get('@.route.default_file',self::$currFileName);
				if(Config::get('@.route.hide_default_file') && $filename === Config::get('@.route.default_file'))
				{
					$filename = '';
				}
				// 没有开启路由或没有匹配到路由
				if(Config::get('@.PATHINFO_ON') || Config::get('@.URL_PARSE_ON'))
				{
					// PATHINFO
					$urlPath = $path;
					if('' !== $filename)
					{
						$filename = $filename . '/';
					}
				}
				else if(Config::get('@.QUERY_PATHINFO_ON'))
				{
					// URL路由解析
					$param[Config::get('@.PATHINFO_QUERY_NAME')] = $path;
				}
				else
				{
					// 传统
					$param[Config::get('@.MODULE_NAME')] = $module;
					$param[Config::get('@.CONTROL_NAME')] = $control;
					$param[Config::get('@.ACTION_NAME')] = $action;
				}
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
			// 域名
			if(isset($urlInfo['host']))
			{
				$domain = $urlInfo['host'];
			}
			else
			{
				$domain = Config::get('@.DOMAIN');
				if(empty($domain))
				{
					$domain = $_SERVER['HTTP_HOST'] . strtr(dirname($_SERVER['SCRIPT_NAME']), '\\','/');
				}
			}
			// 子域名
			if(is_string($subDomain))
			{
				$domain = $subDomain . '.' . $domain;
			}
			// 去除域名后尾的/
			if('/' === substr($domain,-1,1))
			{
				$domain = substr($domain,0,-1);
			}
			if(!empty($param))
			{
				if(false === strpos($urlPath,'?'))
				{
					$query = '?' . http_build_query($param);
				}
				else
				{
					$query = '&' . http_build_query($param);
				}
			}
			// 锚点支持
			if(isset($urlInfo['fragment']))
			{
				$fragment = '#' . $urlInfo['fragment'];
			}
			else 
			{
				$fragment = '';
			}
			$url = "{$protocol}{$domain}/{$filename}{$urlPath}{$query}{$fragment}";
			return $url;
		}
	}
	/**
	 * 处理URL路由
	 * @param unknown $path
	 * @param unknown $param
	 * @return unknown|boolean
	 */
	private static function parseUrlRoute($path,&$param,&$filename)
	{
		foreach(self::$routeRules as $rule => $cfg)
		{
			if($cfg['url'] === $path)
			{
				$isExists = true;
				foreach($cfg['fields'] as $field)
				{
					if(!isset($param[$field['name']]))
					{
						$isExists = false;
						break;
					}
				}
				if($isExists)
				{
					foreach($cfg['fields'] as $field)
					{
						if(!checkRegexTypeValue($field['type'],$field['lengthStart'],$field['lengthEnd'],$param[$field['name']]))
						{
							$isExists = false;
							break;
						}
					}
					if($isExists)
					{
						break;
					}
				}
			}
		}
		if($isExists)// 符合路由规则
		{
			$result = preg_replace_callback(
					'/\[([^\]]+)\]/i',
					function($matches)use(&$param){
						if(false !== strpos($matches[1],':'))
						{
							try {
								list($name) = explode(':',$matches[1]);
							} catch (Exception $e) {
							}
						}
						else
						{
							$name = &$matches[1];
						}
						$result = $param[$name];
						unset($param[$name]);
						return $result;
					},
					$rule,
					-1);
			if('' === $cfg['filename'])
			{
				$filename = Config::get('@.route.default_file',self::$currFileName);
			}
			else 
			{
				$filename = $cfg['filename'];
			}
			if(Config::get('@.route.hide_default_file') && $filename === Config::get('@.route.default_file'))
			{
				$filename = '';
			}
			return $result;
		}
		else
		{
			return false;
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