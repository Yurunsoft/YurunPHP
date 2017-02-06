<?php
/**
 * 配置驱动类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class Config extends Driver
{
	/**
	 * 当前驱动名称
	 * @var type 
	 */
	public static $driverName = '';
	/**
	 * 默认配置实例
	 * @var type 
	 */
	private static $defaultObject = null;
	/**
	 * 初始化前
	 */
	protected static function __initBefore()
	{
		static::$driverName = 'Config';
	}
	/**
	 * 初始化后
	 */
	protected static function __initAfter()
	{
		// 添加公共配置组
		self::$defaultObject = self::create(array('type'=>'Php'),'@');
	}
	/**
	 * 项目初始化前
	 */
	public static function __onAppLoadBefore()
	{
		// 项目配置文件目录
		defined('APP_CONFIG') or define('APP_CONFIG', APP_PATH . Yurun::$config['CONFIG_PATH'] . DIRECTORY_SEPARATOR);
	}
	/**
	 * 创建实例后
	 * @param array $option
	 * @param string $alias
	 * @param mixed $object
	 */
	protected static function __createAfter(&$option,$alias,&$object)
	{
		if ('@' !== $alias && $object->merge && null !== self::$defaultObject)
		{
			// 将数据合并到公用项
			self::$defaultObject->set($object->get());
		}
		$data = $object->get('CONFIGS');
		if(is_array($data))
		{
			// 循环加载配置文件中的配置文件
			foreach($data as $name => $option)
			{
				self::create($option,$name);
			}
		}
	}
	/**
	 * 保存配置
	 * @param string $name
	 * @param array $option
	 * @return boolean
	 */
	public static function save($name, $option = array())
	{
		$obj = self::getInstance($name);
		if ($obj)
		{
			return $obj->save($option);
		}
		else
		{
			return false;
		}
	}
	/**
	 * 设置数据
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public static function set($name, $value = null)
	{
		$names = parseCfgName($name);
		if (isset($names[0]))
		{
			$first = array_shift($names);
			$obj = self::getInstance($first);
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
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($name = '@', $default = false)
	{
		$names = parseCfgName($name);
		if (isset($names[0]))
		{
			$first = array_shift($names);
			$obj = self::getInstance($first);
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
	 * @param string $name
	 * @return boolean
	 */
	public static function remove($name)
	{
		$names = parseCfgName($name);
		if (isset($names[1]))
		{
			// 删除数据
			$first = array_shift($names);
			$obj = self::getInstance($first);
			return $obj->remove($name);
		}
		else
		{
			// 删除配置分组
			unset(self::$instances['Config'][$name]);
			return true;
		}
	}
	/**
	 * 清空数据
	 */
	public static function clear()
	{
		self::$defaultObject->clear();
		self::$instances['Config'] = array(
			'@'	=>	self::$defaultObject
		);
	}
}