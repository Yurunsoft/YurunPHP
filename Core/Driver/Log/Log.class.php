<?php
/**
 * 日志驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Log extends Driver
{
	/**
	 * 当前驱动名称
	 * @var type 
	 */
	public static $driverName = '';
	protected static function __initBefore()
	{
		static::$driverName = 'Log';
	}
	protected static function __initAppBefore()
	{
		// 项目配置文件目录
		defined('APP_LOG') or define('APP_LOG', APP_PATH . Config::get('@.LOG_PATH') . DIRECTORY_SEPARATOR);
	}
	public static function add($content, $option = array() , $alias = null)
	{
		$object = self::getInstance($alias);
		if($object)
		{
			$object->add($content, $option);
		}
	}
	public static function save()
	{
		foreach(self::$instances['Log'] as $instance)
		{
			$instance->save();
			$instance->data = array();
		}
	}
}