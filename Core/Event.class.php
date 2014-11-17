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
	 */
	public static function register($event, $callback)
	{
		if (! isset(self::$events[$event]))
		{
			self::$events[$event] = array ();
		}
		self::$events[$event][] = $callback;
	}
	
	/**
	 * 触发事件(监听事件)
	 *
	 * @param name $event        	
	 * @param boolean $once        	
	 * @return mixed
	 */
	public static function trigger($event, $once = false,&$a=null,&$b=null,&$c=null,&$d=null,&$e=null)
	{
		if (isset(self::$events[$event]))
		{
			// 返回的值们
			$return = array ();
			// 获取参数
			$args = func_get_args();
			// 删除前2个，你懂的
			array_splice($args, 0, 2);
			// 参数数量
			$an=count($args);
			// 组合执行命令
			$code='return call_user_func_array($item, array(';
			for($i=0;$i<$an;++$i)
			{
				// 判断是否需要引用参数
				if($i<=self::$args_num)
				{
					$code.="&\$args[{$i}],";
				}
				else
				{
					$code.="\$args[{$i}],";
				}
			}
			if(substr($code,-1)==='(')
			{
				$code.='));';
			}
			else
			{
				$code=substr($code,0,-1).'));';
			}
			// 执行事件
			foreach (self::$events[$event] as $item)
			{
				if ($once)
				{
					$return=eval($code);
					break;
				}
				else
				{
					$return[] = eval($code);
				}
			}
			// 引用参数返回值
			$str='a';
			for($i=0;$i<$an;++$i)
			{
				$$str=$args[$i];
				++$str;
			}
			return $return;
		}
		else
		{
			return false;
		}
	}
}