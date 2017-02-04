<?php
class Plugin
{
	private static $config;
	/**
	 * 加载插件
	 */
	public static function load()
	{
		// 实例化配置驱动
		self::$config = Config::create(Config::get('@.PLUGIN_OPTION'));
		$plugins = self::$config->get();
		$pluginPath = Config::get('@.PLUGIN_PATH');
		if(!is_dir($pluginPath))
		{
			$pluginPath = APP_PATH . $pluginPath;
		}
		$pluginPath .= DIRECTORY_SEPARATOR;
		// 加载插件
		foreach ($plugins as $plugin)
		{
			if($plugin['is_open'])
			{
				include_once $pluginPath . "{$plugin['name']}/{$plugin['name']}.php";
			}
		}
	}
	/**
	 * 保存插件信息
	 */
	public static function save()
	{
		self::$config->save();
	}
	/**
	 * 获取插件信息
	 * @param type $name
	 * @return type
	 */
	public static function get($name = null)
	{
		return self::$config->get($name);
	}
	/**
	 * 设置插件信息
	 * @param type $name
	 * @param type $value
	 * @return type
	 */
	public static function set($name,$value)
	{
		return self::$config->set($name,$value);
	}
}