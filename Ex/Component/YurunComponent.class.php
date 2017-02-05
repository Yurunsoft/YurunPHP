<?php
/**
 * YurunPHP控件类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YurunComponent
{
	public static function __callstatic($name, $arguments)
	{
		$args = isset($arguments[0]) && is_array($arguments[0]) ? $arguments[0] : array();
		$return = 'get'===substr($name,0,3);
		if($return)
		{
			$name = substr($name,3);
		}
		$class = 'YC'.ucfirst($name);
		if(!class_exists($class))
		{
			$class = 'YCBase';
		}
		$obj = new $class($args, $name);
		if($return)
		{
			return $obj;
		}
		else
		{
			$obj->begin();
			$obj->end();
		}
	}
	public static function arrayToStr(&$attrs)
	{
		$keys = array_keys($attrs);
		$result = '';
		foreach($keys as $key)
		{
			if(isset($attrs[$key][0]) && '#' === $attrs[$key][0])
			{
				$attrs[$key] = substr($attrs[$key],1);
			}
			else
			{
				$attrs[$key] = '\'' . addcslashes($attrs[$key],'\'') . '\'';
			}
			$result .= "'{$key}'=>{$attrs[$key]},";
		}
		return 'array('.substr($result,0,-1).')';
	}
	function parseValues($str,$arr)
	{
		return preg_replace_callback(
				'/{([^}]+)}/',
				function($matches) use($arr){
					return $arr[$matches[1]];
				},
				$str,
				-1);
	}
	/**
	 * 将控件属性转为渲染用字符串
	 * @return string
	 */
	public static function &parseAttrsString($attrs,$excludeAttrs = array())
	{
		$result = '';
		if(is_array($attrs))
		{
			if(isset($attrs['disabled']) && 0==$attrs['disabled'])
			{
				unset($attrs['disabled']);
			}
			if(isset($attrs['readonly']) && 0==$attrs['readonly'])
			{
				unset($attrs['readonly']);
			}
			foreach($attrs as $key=>$attr)
			{
				if(false===in_array($key,$excludeAttrs))
				{
					$result .= " {$key}=\"{$attr}\"";
				}
			}
		}
		return $result;
	}
	public static function getTemplatePHP($tag,$php)
	{
		$class = 'YC'.ucfirst($tag);
		if(!class_exists($class))
		{
			$class = 'YCBase';
		}
		return $class::__getTemplatePHP($php);
	}
}