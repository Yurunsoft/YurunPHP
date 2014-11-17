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
			// 取出模块控制器动作名
			$module = Dispatch::module();
			$control = Dispatch::control();
			$action = Dispatch::action();
			// 获取主题名称
			$themeName = $this->getThemeName($theme);
			if ($themeName !== '')
			{
				$themeName .= '/';
			}
			if (empty($template))
			{
				// 当前控制器动作对应的模版，根据是否启用模块，自动识别模版目录
				if ($module === '')
				{
					$template = APP_TEMPLATE . "{$themeName}{$control}/{$action}" . Config::get('@.TEMPATE_EXT');
				}
				else
				{
					$template = APP_MODULE . $module . '/' . Config::get('@.TEMPLATE_FOLDER') . "/{$themeName}{$control}/{$action}" . Config::get('@.TEMPATE_EXT');
				}
			}
			// 替换项目模版路径
			else if (stripos($template, '@app/') !== false)
			{
				$template = str_replace('@app/', APP_TEMPLATE . $themeName, $template);
			}
			// 替换模块模版路径
			else if (stripos($template, '@module/') !== false)
			{
				$template = str_replace('@module/', APP_MODULE . "/{$module}/" . Config::get('@.TEMPLATE_FOLDER') . "/{$themeName}", $template);
			}
			else
			{
				// 取出斜杠出现次数
				$num = substr_count($template, '/');
				switch ($num)
				{
					case 0 :
						// 动作名
						$template = APP_MODULE . $module . '/' . Config::get('@.TEMPLATE_FOLDER') . "/{$themeName}{$control}/{$template}";
						break;
					case 1 :
						// 控制器+动作名
						$template = APP_MODULE . $module . '/' . Config::get('@.TEMPLATE_FOLDER') . "/{$themeName}{$template}";
						break;
					case 2 :
						// 模块名+控制器+动作名
						$arr = explode('/', $template);
						$arr[1] = $themeName . $arr[1];
						$template = APP_MODULE . implode('/', $arr);
						break;
					default :
						return false;
						break;
				}
			}
			if (stripos($template, '@m/') !== false)
			{
				$template = str_replace('@m/', "{$module}/", $template);
			}
			if (stripos($template, '@c/') !== false)
			{
				$template = str_replace('@c/', "{$control}/", $template);
			}
			if (stripos($template, '@a/') !== false)
			{
				$template = str_replace('@a/', "{$action}/", $template);
			}
			// 返回结果
			return $template . Config::get('@.TEMPLATE_EXT');
		}
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
				return Config::get('@.THEME_DEFAULT', null);
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
		if (is_file($template))
		{
			$file = $template;
		}
		else
		{
			// 解析出模版文件名
			$file = $this->getTemplateFile($template, $theme);
		}
		if (Config::get('@.TEMPLATE_ENGINE_ON'))
		{
			// 启用模版引擎
			$cacheFile = Cache::getObj('file')->getFileName($file);
			// 判断模版缓存是否存在
			if (! Cache::cacheExists($file))
			{
				// 没有模版缓存，解析模版并写入缓存
				Cache::set($file, self::parseTemplate($file), array ('expire_on' => false));
			}
			// 执行
			echo self::execTemplate($cacheFile);
		}
		else
		{
			echo self::execTemplate($file);
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
	 * 执行模版，返回执行后内容
	 *
	 * @param string $file        	
	 */
	public function execTemplate($file)
	{
		ob_start();
		try
		{
			if (is_array($file))
			{
				foreach ($file as $value)
				{
					if (is_file($value))
					{
						include $value;
						break;
					}
				}
			}
			else
			{
				include $file;
			}
			return ob_get_clean();
		}
		catch (Exception $ex)
		{
			ob_end_clean();
			return '';
		}
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
		// 判断绑定的控制器存在
		if(!empty($this->control))
		{
			// 调用控制器中的方法
			call_user_func_array(array($this->control,$name),$arguments);
		}
	}
}