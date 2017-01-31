<?php
class Plugin
{
//	private static $plugins = array();
	/**
	 * 加载插件
	 */
	public static function load()
	{
		// 获取插件列表
//		self::$plugins = include APP_CONFIG . '';
		// 加载插件
		foreach (self::$plugins as $plugin)
		{
			include_once APP_PLUGIN . "{$plugin}/{$plugin}.php";
		}
	}
}