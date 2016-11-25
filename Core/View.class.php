<?php
/**
 * 视图类
 * @author Yurun <admin@yurunsoft.com>
 */
class View extends ArrayData
{
	// 是否使用主题
	protected $theme;
	// 内容类型
	protected $contentType='text/html';
	// 对应的控制器
	protected $control;
	protected $pathStack=array();
	// 用于include的临时theme变量
	protected $themeStack=array();
	// 模版引擎对象
	protected $engine;
	function __construct($theme = null,$control=null)
	{
		if (is_string($theme))
		{
			$this->theme = $theme;
		}
		if(is_object($control))
		{
			$this->control=$control;
		}
		if (Config::get('@.TEMPLATE_ENGINE_ON'))
		{
			$class = Config::get('@.TEMPLATE_ENGINE');
			$this->engine = new $class;
		}
	}

	/**
	 * 获取模版文件名，可能返回文本或文本数组
	 *
	 * @param string $template        	
	 * @param string $theme        	
	 * @return string
	 */
	public function getTemplateFile($template = null, $theme = null)
	{
		if(empty($theme))
		{
			$theme=array_pop($this->themeStack);
			$this->themeStack[] = $theme;
		}
		if (is_file($template))
		{
			// 是文件就不用解析了
			return $template;
		}
		else
		{
			if(is_string($template))
			{
				// 主题名
				if (false !== stripos($template, '@theme'))
				{
					$template = str_replace('@theme', $this->getThemeName($theme), $template);
				}
				// 模块名
				if (false !== stripos($template, '@module'))
				{
					$template = str_replace('@module', Dispatch::module(), $template);
				}
				// 控制器名
				if (false !== stripos($template, '@control'))
				{
					$template = str_replace('@control', Dispatch::control(), $template);
				}
				// 动作名
				if (false !== stripos($template, '@action'))
				{
					$template = str_replace('@action', Dispatch::action(), $template);
				}
				
				// 项目模版目录
				if (false !== stripos($template, '@app/'))
				{
					$template = str_replace('@app/', APP_TEMPLATE, $template);
				}
				// 模块模版目录
				else if (false !== stripos($template, '@m/'))
				{
					if(Config::get('@.MODULE_ON'))
					{
						$template = str_replace('@m/', 
								APP_MODULE . Dispatch::module() . '/' . Config::get('@.TEMPLATE_FOLDER').'/', $template);
					}
					else
					{
						$template = str_replace('@m/',APP_TEMPLATE, $template);
					}
				}
				else 
				{
					$arr=explode('/',$template);
					$s = count($arr);
					if(1 === $s)
					{
						// 动作
						list($action) = $arr;
					}
					else if(2 === $s)
					{
						// 控制器/动作
						list($control,$action) = $arr;
					}
					else if(3 === $s)
					{
						// 模块/控制器/动作
						list($module,$control,$action) = $arr;
					}
					else if(4 === $s)
					{
						// 主题/模块/控制器/动作
						list($themeName,$module,$control,$action) = $arr;
					}
					else
					{
						list($themeName,$module,$control) = $arr;
						$action=implode('/',array_slice($arr,3));
					}
				}
			}
			if(empty($template) || isset($arr))
			{
				$path=array_pop($this->pathStack);
				$this->pathStack[] = $path;
				if('/'!==$template[0] && null!==$path)
				{
					$template=$path.$template;
				}
				else 
				{
					if(!isset($themeName))
					{
						$themeName = $this->getThemeName($theme);
					}
					if(!isset($module))
					{
						$module = Dispatch::module();
					}
					if(!isset($control))
					{
						$control = Dispatch::control();
					}
					if(!isset($action))
					{
						$action = Dispatch::action();
					}
					if(Config::get('@.MODULE_ON'))
					{
						if(Config::get('@.MODULE_TEMPLATE'))
						{
							$template=APP_MODULE."{$module}/".Config::get('@.TEMPLATE_FOLDER')."/{$themeName}/{$control}/{$action}";
						}
						else
						{
							$template=APP_TEMPLATE."{$themeName}/{$module}/{$control}/{$action}";
						}
					}
					else
					{
						$template=APP_TEMPLATE."{$themeName}/{$control}/{$action}";
					}
				}
			}
			$template.=Config::get('@.TEMPLATE_EXT');
		}
		return $template;
	}
	
	/**
	 * 获取主题名称
	 *
	 * @param string $theme        	
	 * @return mixed
	 */
	public function getThemeName($theme = null)
	{
		if (Config::get('@.THEME_ON'))
		{
			// 参数传入的主题
			if (is_string($theme) && '' !== $theme)
			{
				return $theme;
			}
			// 类设定的主题
			else if (is_string($this->theme) && '' !== $this->theme)
			{
				return $this->theme;
			}
			// 配置中默认主题
			else
			{
				return Config::get('@.THEME', null);
			}
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 显示模版内容
	 *
	 * @param string $template        	
	 * @param mixed $theme        	
	 */
	public function display($template = null, $theme = null)
	{
		// 设置内容类型和编码
		header('Content-type: ' . $this->contentType . '; charset=utf-8');
		if(empty($theme))
		{
			$theme = $this->theme;
		}
		$this->themeStack[] = $theme;
		$this->showTemplate($template,$theme);
		array_pop($this->themeStack);
	}
	
	public function getHtml($template = null, $theme = null)
	{
		// 解析出模版文件名
		$file = $this->getTemplateFile($template, $theme);
		$this->pathStack[] = dirname($file).'/';
		// 将view层数据转为变量，方便模版中直接调用
		extract($this->data);
		// 模版引擎处理后的文件名
		$file = $this->templateEngineParse($file);
		ob_start();
		include $file;
		return ob_get_clean();
	}
	
	public function _R_include($template = null, $theme = null)
	{
		$this->showTemplate($template,$theme);
	}
	
	private function showTemplate($template = null, $theme = null)
	{
		// 解析出模版文件名
		$file = $this->getTemplateFile($template, $theme);
		$this->pathStack[] = dirname($file).'/';
		// 将view层数据转为变量，方便模版中直接调用
		extract($this->data);
		// 返回模版引擎处理后的文件名
		include $this->templateEngineParse($file);
		array_pop($this->pathStack);
	}
	
	private function templateEngineParse($file)
	{
		if (Config::get('@.TEMPLATE_ENGINE_ON'))
		{
			$cacheFileName = 'Template/' . md5($file);
			// 启用模版引擎
			$cacheFile = Cache::getObj('File')->getFileName($cacheFileName);
			if($this->cacheIsExpired($cacheFile))
			{
				// 判断模版缓存是否存在
				$t=$this->parseTemplate($file);
				// 没有模版缓存，解析模版并写入缓存
				Cache::set($cacheFileName, $t, array ('raw'=>true));
			}
			// 执行
			return $cacheFile;
		}
		else
		{
			return $file;
		}
	}
	private function cacheIsExpired($file)
	{
		if(Config::get('@.TEMPLATE_CACHE_ON') && is_file($file))
		{
			return filemtime($file) + Config::get('@.TEMPLATE_CACHE_EXPIRE') < $_SERVER['REQUEST_TIME'];
		}
		else 
		{
			return true;
		}
	}
	/**
	 * 解析模版，返回解析后内容
	 *
	 * @param string $file        	
	 */
	public function parseTemplate($file)
	{
		return $this->engine->parse($file);
	}
	
	/**
	 * 设置主题
	 *
	 * @param string $theme        	
	 */
	public function setTheme($theme)
	{
		$this->theme = $theme;
	}
	
	/**
	 * 获取主题
	 *
	 * @return string
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * 设置控制器
	 * @param object $control
	 */
	public function setControl($control)
	{
		$this->control = $control;
	}
	
	/**
	 * 获取控制器
	 * @return object
	 */
	public function getControl()
	{
		return $this->control;
	}

	// 设置内容类型
	public function setContentType($contentType)
	{
		$this->contentType=$contentType;
	}

	// 获取内容类型
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * 魔术方法
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments)
	{
		// 不存在的方法
		$name2='_R_' . $name;
		if(method_exists($this,$name2))
		{
			return call_user_func_array(array($this,$name2),$arguments);
		}
		// 判断绑定的控制器存在
		else if(!empty($this->control) && is_callable(array($this->control,$name)))
		{
			// 调用控制器中的方法
			return call_user_func_array(array($this->control,$name),$arguments);
		}
	}
}