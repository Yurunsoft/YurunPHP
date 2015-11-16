<?php
/**
 * 配置驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class Config extends Driver
{
	/**
	 * 初始化
	 */
	public static function init()
	{
		parent::init();
		// 添加公共配置组
		self::create('@');
		// 载入框架配置
		self::create('Core', 'php', PATH_CONFIG . 'config.php');
	}
	/**
	 * 创建配置项
	 *
	 * @param string $name        	
	 * @param string $type        	
	 * @param bool $merge
	 * @return boolean
	 */
	public static function create($name, $type = 'php', $merge=true)
	{
		if (self::exists($name))
		{
			return false;
		}
		else
		{
			$args = func_get_args();
			if (isset($args[1]))
			{
				$t = $args[0];
				$args[0] = $args[1];
				$args[1] = $t;
			}
			else
			{
				$args[1] = $args[0];
				$args[0] = $type;
			}
			$obj = call_user_func_array(array ('parent','create'), $args);
			$data=$obj->get('CONFIGS');
			if(is_array($data))
			{
				foreach($data as $val)
				{
					call_user_func_array(array ('Config','create'), $val);
				}
			}
			if ($obj !== false && $name !== '@')
			{
				if($merge)
				{
					// 将数据合并到公用项
					self::getObj('@')->set($obj->get());
				}
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	/**
	 * 保存配置
	 *
	 * @access public static
	 * @param string $name        	
	 * @param string $fileName        	
	 */
	public static function save($name, $fileName = null)
	{
		$obj = self::getObj($name);
		if ($obj)
		{
			return $obj->save($fileName);
		}
		else
		{
			return false;
		}
	}
	/**
	 * 设置数据
	 *
	 * @param type $name        	
	 * @param type $value        	
	 * @return boolean
	 */
	public static function set($name, $value = null)
	{
		$names = explode('.', $name);
		if (count($names) > 0)
		{
			$first = array_shift($names);
			$obj = self::getObj($first);
			if ($obj)
			{
				return $obj->setVal($names, $value);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	/**
	 * 获取数据
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public static function get($name, $default = false)
	{
		$names = explode('.', $name);
		if (count($names) > 0)
		{
			$first = array_shift($names);
			$obj = self::getObj($first);
			if ($obj)
			{
				return $obj->get($names, $default);
			}
			else
			{
				return $default;
			}
		}
		else
		{
			return $default;
		}
	}
	/**
	 * 删除数据
	 *
	 * @param string $name        	
	 * @return boolean
	 */
	public static function remove($name)
	{
		$names = explode('.', $name);
		$arrLen = count($names);
		if ($arrLen === 1)
		{
// 			var_dump(self::$instance['Config']);exit;
			// 删除配置分组
			unset(self::$instance['Config'][$name]);
			return true;
		}
		else
		{
			// 删除数据
			$first = array_shift($names);
			$obj = self::getObj($first);
			return $obj->remove($names);
		}
	}
	/**
	 * 清空数据
	 */
	public static function clear()
	{
		self::init();
	}
	/**
	 * 获取配置项数据数量
	 *
	 * @param string $name        	
	 * @return int
	 */
	public static function count($name)
	{
		$name = self::$configs[$name];
		if (isset(self::$instance[$name]))
		{
			return count(self::$instance[$name]);
		}
		else
		{
			return 0;
		}
	}
}
Config::init();