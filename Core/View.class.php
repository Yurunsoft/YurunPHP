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
				if (stripos($template, '@theme') !== false)
				{
					$template = str_replace('@theme', $this->getThemeName($theme), $template);
				}
				// 模块名
				if (stripos($template, '@module') !== false)
				{
					$template = str_replace('@module', Dispatch::module(), $template);
				}
				// 控制器名
				if (stripos($template, '@control') !== false)
				{
					$template = str_replace('@control', Dispatch::control(), $template);
				}
				// 动作名
				if (stripos($template, '@action') !== false)
				{
					$template = str_replace('@action', Dispatch::action(), $template);
				}
				
				// 项目模版目录
				if (stripos($template, '@app/') !== false)
				{
					$template = str_replace('@app/', APP_TEMPLATE, $template);
				}
				// 模块模版目录
				else if (stripos($template, '@m/') !== false)
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
// 					$template='';
					switch(count($arr))
					{
						case 1:
							// 动作
							list($action) = $arr;
							break;
						case 2:
							// 控制器/动作
							list($control,$action) = $arr;
							break;
						case 3:
							// 模块/控制器/动作
							list($module,$control,$action) = $arr;
							break;
						case 4:
							// 主题/模块/控制器/动作
							list($themeName,$module,$control,$action) = $arr;
							break;
						default:
							list($themeName,$module,$control) = $arr;
							$action=implode('/',array_slice($arr,3));
							break;
					}
					
				}
			}
			if(empty($template) || isset($arr))
			{
				$path=array_pop($this->pathStack);
				array_push($this->pathStack,$path);
				if($template[0]!=='/' && $path!==null)
				{
					$template="{$path}{$template}";
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
			if (is_string($theme) && ! empty($theme))
			{
				return $theme;
			}
			// 类设定的主题
			else if (is_string($this->theme) && ! empty($this->theme))
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
		header("Content-type: {$this->contentType}; charset=utf-8");
		$this->showTemplate($template,$theme);
	}
	
	public function getHtml($template = null, $theme = null)
	{
		// 解析出模版文件名
		$file = $this->getTemplateFile($template, $theme);
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
		array_push($this->pathStack,dirname($file).'/');
		// 返回模版引擎处理后的文件名
		include $this->templateEngineParse($file);
		array_pop($this->pathStack);
	}
	
	private function templateEngineParse($file)
	{
		if (Config::get('@.TEMPLATE_ENGINE_ON'))
		{
			// 启用模版引擎
			$cacheFile = Cache::getObj('file')->getFileName($file);
			// 判断模版缓存是否存在
			if (! Cache::cacheExists($file))
			{
				// 没有模版缓存，解析模版并写入缓存
				Cache::set($file, $this->parseTemplate($file), array ('expire_on' => false));
			}
			// 执行
			return $cacheFile;
		}
		else
		{
			return $file;
		}
	}
	
	/**
	 * 解析模版，返回解析后内容
	 *
	 * @param string $file        	
	 */
	public function parseTemplate($file)
	{
		$content = file_get_content($file);
		return $content;
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
		$name2="_R_{$name}";
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