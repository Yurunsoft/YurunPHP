<?php
/**
 * 日志驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Log extends Driver
{
	private static $obj;
	private static $data=array();
	/**
	 * 初始化
	 */
	public static function init()
	{
		parent::init();
		self::$obj=self::create(Config::get('@.LOG_TYPE', array ()));
	}
	public static function add($content)
	{
		self::$data[]=array('content'=>$content,'time'=>date(Config::get('@.LOG_DATE_FORMAT')));
	}
	public static function save()
	{
		self::$obj->save(self::$data);
	}
}
Log::init();