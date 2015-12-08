<?php
/**
 * 公共函数集
 * @author Yurun <admin@yurunsoft.com>
 */
/**
 * 引入多个文件
 * @param array $files        	
 * @param boolean $all        	
 * @return boolean
 */
function require_once_multi($files, $all = true)
{
	$type = gettype($files);
	if('array' === $type)
	{
		foreach ($files as $value)
		{
			if (is_file($value))
			{
				require_once $value;
				if (! $all)
				{
					return true;
				}
			}
		}
		return false;
	}
	else if('string' === $type)
	{
		require_once $files;
		return true;
	}
	else
	{
		return false;
	}
}
/**
 * 获取规范的类命名第一段
 * @param type $str        	
 * @return type
 */
function getClassFirst($str)
{
	preg_match_all('/^([A-Z]{1}[^A-Z]*)\S*/', $str, $out);
	return $out[1][0];
}
function enumFiles($path, $event)
{
	if ('/' !== substr(strtr($path, '\\', '/'), '-1', 1))
	{
		$path .= '/';
	}
	$dir = dir($path);
	while (false !== ($file = $dir->read()))
	{
		if ('.' !== $file && '..' !== $file)
		{
			$fileName = $path . $file;
			if (is_dir($fileName))
			{
				enumFiles($fileName, $event);
			}
			else
			{
				call_user_func_array($event, array ($fileName));
			}
		}
	}
	$dir->close();
}
/**
 * 执行过滤操作
 * @param mixed $value        	
 * @param mixed $filter        	
 * @return mixed
 */
function execFilter($value, $filter)
{
	if(!empty($filter))
	{
		if (! is_array($filter))
		{
			$filter = explode(',', $filter);
		}
		foreach ($filter as $item)
		{
			$value = call_user_func_array($item, array ($value));
		}
	}
	return $value;
}
/**
 * 随机多个数字，可设定是否重复
 * @param int $min        	
 * @param int $max        	
 * @param int $num        	
 * @param boolean $re        	
 * @return array
 */
function randomNums($min, $max, $num, $re = false)
{
	$arr = array ();
	$t = 0;
	$i = 0;
	// 如果数字不可重复，防止无限死循环
	if (! $re)
	{
		$num = min($num, $max - $min + 1);
	}
	do
	{
		// 取随机数
		$t = mt_rand($min, $max);
		if (! $re && isset($arr[$t]))
		{
			// 数字重复
			continue;
		}
		$arr[$t] = 1;
		++ $i;
	}
	while ($i < $num);
	return $arr;
}
/**
 * boolval函数
 */
if (!function_exists('boolval'))
{
	function boolval($val)
	{
		return (bool) $val;
	}
}
/**
 * 从HTML代码中提取图片
 * @param string $str
 * @return string
 */
function getImages($str)
{
	preg_match_all("/<img([^>]*)\s*src=('|\")([^'\"]+)('|\")/i",$str,$matchs);
	return $matchs[3];
}
/**
 * 将数据查询结果自动编号
 * @param array $arr
 * @param name $name
 * @return array
 */
function autoNumber(&$arr,$name)
{
	$s=count($arr);
	for($i=0;$i<$s;++$i)
	{
		$arr[$i][$name]=$i+1;
	}
}
/**
 * 将数组每个成员都设置为引用
 * @param array $array
 * @return array
 */
function arrayRefer(&$array)
{
	$result=array();
	foreach($array as &$item)
	{
		$result[]=&$item;
	}
	return $result;
}
/**
 * 多维数组递归合并
 */
function multimerge()
{
	$arrs = func_get_args ();
	$merged = array ();
	$s = count($arrs);
	for($i=0;$i<$s;++$i)
	{
		$array = $arrs[$i];
		if (!is_array($array))
		{
			continue;
		}
		foreach ( $array as $key => $value )
		{
			if (is_string ( $key ))
			{
				if (is_array ( $value ) && isset($merged[$key]) && is_array ( $merged [$key] ))
				{
					$merged [$key] = multimerge ( $merged [$key], $value );
				}
				else
				{
					$merged [$key] = $value;
				}
			}
			else
			{
				$merged [] = $value;
			}
		}
	}
	return $merged;
}