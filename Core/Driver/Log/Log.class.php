<?php
/**
 * 日志驱动类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class Log extends Driver
{
	/**
	 * 当前驱动名称
	 * @var type 
	 */
	public static $driverName = '';
	
	/**
	 * 初始化前
	 */
	protected static function __initBefore()
	{
		static::$driverName = 'Log';
	}
	/**
	 * 项目初始化前
	 */
	protected static function __onAppLoadBefore()
	{
		// 项目配置文件目录
		defined('APP_LOG') or define('APP_LOG', APP_PATH . Config::get('@.LOG_PATH') . DIRECTORY_SEPARATOR);
	}
	/**
	 * 添加日志
	 * @param string $content
	 * @param array $option
	 * @param string $alias
	 */
	public static function add($content, $option = array() , $alias = null)
	{
		$object = self::getInstance($alias);
		if($object)
		{
			$object->add($content, $option);
		}
	}
	/**
	 * 保存日志
	 */
	public static function save()
	{
		foreach(self::$instances['Log'] as $instance)
		{
			$instance->save();
			$instance->data = array();
		}
	}
}