<?php
/**
 * 事件类
 * @author Yurun <admin@yurunsoft.com>
 */
class Event
{
	// 事件绑定记录
	private static $events;

	// 引用参数数量
	private static $args_num=5;
	/**
	 * 初始化
	 */
	public static function init()
	{
		self::$events = array ();
		// 获取插件列表
		$data = Config::get('Plugin');
		// 加载插件
		foreach ($data as $value)
		{
			include_once APP_PLUGIN . "{$value}/{$value}.php";
		}
		return true;
	}
	
	/**
	 * 注册事件
	 *
	 * @param string $event
	 * @param mixed $callback
	 * @param bool $first 是否优先执行，以靠后设置的为准
	 */
	public static function register($event, $callback, $first=false)
	{
		if (! isset(self::$events[$event]))
		{
			self::$events[$event] = array ();
		}
		if($first)
		{
			array_unshift(self::$events[$event],$callback);
		}
		else 
		{
			self::$events[$event][] = $callback;
		}
	}
	
	/**
	 * 触发事件(监听事件)
	 * 不是引用传参方式，如有需要请使用triggerReference方法
	 * @param name $event        	
	 * @param boolean $once        	
	 * @return mixed
	 */
	public static function trigger($event, &$params=array())
	{
		if (isset(self::$events[$event]))
		{
			foreach (self::$events[$event] as $item)
			{
				if(true === call_user_func($item,$params))
				{
					// 事件返回true时不继续执行其余事件
					return true;
				}
			}
			return false;
		}
		return true;
	}
}