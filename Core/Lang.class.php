<?php
/**
 * 语言类
 * @author Yurun <admin@yurunsoft.com>
 */
class Lang
{
	// 语言数据
	protected static $list;
	
	/**
	 * 初始化
	 */
	public static function init()
	{
		// 默认语言包名
		$default = Config::get('@.DEFAULT_LANG');
		// 加载默认语言包
		self::$list = self::loadLang($default);
		// 判断是否自动判断浏览器语言
		if (Config::get('@.LANG_AUTO'))
		{
			// 获取浏览器语言
			$lang = self::getlang();
			// 浏览器语言和默认语言不一致就加载覆盖
			if ($default != $lang)
			{
				self::$list = array_merge(self::$list, self::loadLang($lang));
			}
		}
	}
	public static function get($name)
	{
		$args = func_get_args();
		if ($args !== array ())
		{
			unset($args[0]);
		}
		if (isset(self::$list[$name]))
		{
			array_unshift($args, self::$list[$name]);
			return call_user_func_array('sprintf', $args);
		}
		else
		{
			return $name;
		}
	}
	
	/**
	 * 获取浏览器head头传入的语言名
	 *
	 * @return boolean
	 */
	public static function getlang()
	{
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches) > 0)
		{
			return strtolower($matches[1]);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 载入语言包数据
	 *
	 * @param string $lang        	
	 * @return array
	 */
	public static function loadLang($lang)
	{
		$list = array ();
		// 框架语言包
		$file = PATH_LANG . "{$lang}.lang.php";
		if (is_file($file))
		{
			$data = include $file;
			$list = array_merge($list, $data);
		}
		// 项目语言包
		$file = APP_LANG . "{$lang}.lang.php";
		if (is_file($file))
		{
			$data = include $file;
			$list = array_merge($list, $data);
		}
		// 获取模块名
		$m = Dispatch::module();
		// 是否开启模块
		if ($m !== '')
		{
			// 模块语言包
			$file = APP_MODULE . "{$m}/" . Config::get('@.LANG_FOLDER') . "/{$lang}.lang.php";
			if (is_file($file))
			{
				$data = include $file;
				$list = array_merge($list, $data);
			}
		}
		return $list;
	}
}
Lang::init();