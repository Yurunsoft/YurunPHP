<?php
/**
 * 公共函数集
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
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
 * 按别名导入文件，在配置文件IMPORT项中配置
 */
function import($name)
{
	$filePath = Config::get('@.IMPORT.' . $name);
	if(false === $filePath)
	{
		return false;
	}
	else
	{
		require_once $filePath;
	}
}
/**
 * 获取第一个单词
 * @param string $str        	
 * @return string
 */
function getFirstWord($str)
{
	preg_match_all('/^(([A-Z]*)[A-Z][^A-Z]*|([A-Z][^A-Z]*))\S*/', $str, $out);
	return '' === $out[2][0] ? $out[1][0] : $out[2][0];
}
/**
 * 获取最后一个单词
 * @param string $str
 * @return string
 */
function getLastWord($str)
{
	preg_match_all('/^\S*([A-Z]{1}[^A-Z]*)/', $str, $out);
	return $out[1][0];
}
/**
 * 枚举文件
 * @param string $path 路径
 * @param callback $event 枚举文件回调。参数：$fileName
 */
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
		$arr[$t] = $t;
		++ $i;
	}
	while ($i < $num);
	return $arr;
}
if (PHP_VERSION < 5.5)
{
	/**
	* boolval函数
	* @return bool
	*/
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
	preg_match_all('/<img([^>]*)\s*src=(\'|")([^\'"]+)(\'|")/i',$str,$matchs);
	return $matchs[3];
}
/**
 * 将数据查询结果自动编号
 * @param array $arr
 * @param string $name
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
function &arrayRefer(&$array)
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
 * @return array
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
/**
 * 将二维数组第二纬某key变为一维的key
 * @param array $array
 * @param string $column
 * @param string $keepOld
 */
function arrayColumnToKey(&$array,$column,$keepOld = false)
{
	$s = count($array);
	for($i=0;$i<$s;++$i)
	{
		$array[$array[$i][$column]] = $array[$i];
		if(!$keepOld)
		{
			unset($array[$i]);
		}
	}
}
/**
 * 将类型转换为正则表达式
 * @param string $type 类型
 * @param int $lengthStart 长度开始
 * @param int $lengthEnd 长度结束
 * @return string
 */
function &convertToRegexType($type,$lengthStart = null,$lengthEnd = null)
{
	$result = null;
	if('int' === $type)
	{
		$result = '\d';
	}
	else if('double' === $type || 'float' === $type)
	{
		$result = '\d+\\.\d+';
		return $result;
	}
	else if('letter' === $type)
	{
		$result = '[a-zA-Z]+';
		return $result;
	}
	else if('big_letter' === $type)
	{
		$result = '[A-Z]+';
		return $result;
	}
	else if('small_letter' === $type)
	{
		$result = '[a-z]+';
		return $result;
	}
	else if('word' === $type)
	{
		$result = '[a-zA-Z0-9_-]+';
		return $result;
	}
	else if(!empty($type))
	{
		return $type;
	}
	else
	{
		$result = '.';
	}
	if($lengthStart > 0)
	{
		if($lengthEnd > $lengthStart)
		{
			$result = "{$result}{{$lengthStart},{$lengthEnd}}";
		}
		else
		{
			$result = "{$result}{{$lengthStart}}";
		}
	}
	else 
	{
		$result .= '+';
	}
	return $result;
}
/**
 * 将类型转换为正则表达式后检测值是否正确
 * @param string $type 类型
 * @param int $lengthStart 长度开始
 * @param int $lengthEnd 长度结束
 * @param mixed $value 值
 * @return bool
 */
function checkRegexTypeValue($type,$lengthStart = null,$lengthEnd = null,$value)
{
	return preg_match('/^' . convertToRegexType($type,$lengthStart,$lengthEnd) . '$/i',$value) > 0;
}
/**
 * 处理name按.分隔，支持\.转义不分隔
 * @param string $name
 */
function &parseCfgName($name)
{
	$result = preg_split('#(?<!\\\)\.#', $name);
	array_walk($result,function(&$value,$key){
		if(false !== strpos($value,'\.'))
		{
			$value = str_replace('\.','.',$value);
		}
	});
	return $result;
}
/**
 * 根据控制器名和动作自动加载并实例化
 * @param string $control
 * @param string $action
 */
function &autoLoadControl($control,$action)
{
	$currModulePath = APP_MODULE . Dispatch::module() . '/Control/';
	$controlFile = $control . 'Control.class.php';
	$actionFile =  $control . '/' . $action . '.php';
	if (require_once_multi(array (
				$currModulePath . $actionFile,			// 模块控制器动作目录
				$currModulePath . $controlFile,			// 模块控制器目录
				APP_LIB . 'Control/' . $actionFile,				// 项目控制器动作目录
				APP_LIB . 'Control/' . $controlFile,				// 项目控制器目录
				ROOT_PATH . 'Ex/Lib/' . $actionFile, 	// 框架控制器动作扩展目录
				ROOT_PATH . 'Ex/Lib/' . $controlFile 	// 框架控制器扩展目录
			),false))
	{
		$r = new ReflectionClass($control . 'Control');
		if($r->isInstantiable())
		{
			return $r->newInstance();
		}
		else
		{
			return false;
		}
	}
	else
	{
		$result = false;
		return $result;
	}
}
/**
 * 根据组名获取数据值，比如<input type="text" name="group.title"/>，传入group
 * @param string $group
 * @return array
 */
function &getDataByGroup($group,$method = 'all')
{
	$fields = getFieldsByGroup($group,$method);
	$data = array();
	// 遍历取出字段对应的数据
	foreach($fields as $key => $field)
	{
		$data[$field] = Request::$method($key);
	}
	return $data;
}
/**
 * 根据组名获取数据值，比如<input type="text" name="group.title[]"/>，传入group
 * @param string $group
 * @return array
 */
function &getDataArrayByGroup($group,$method = 'all')
{
	$fields = getFieldsByGroup($group,$method);
	$data = array();
	// 遍历取出字段对应的数据
	foreach($fields as $key => $field)
	{
		$arr = Request::$method($key,array());
		$s = count($arr);
		for($i=0;$i<$s;++$i)
		{
			if(!isset($data[$i]))
			{
				$data[$i] = array();
			}
			$data[$i][$field] = $arr[$i];
		}
	}
	return $data;
}
/**
 * 根据组名获取字段们
 * @param string $group
 * @return array
 */
function &getFieldsByGroup($group,$method = 'all')
{
	$isGroup = '' !== $group && null !== $group;
	$group = $isGroup ? ($group . '_') : '';
	$groupLen = strlen($group);
	$fields = array();
	$data = Request::$method();
	foreach($data as $key=>$value)
	{
		if(!$isGroup || substr($key,0,$groupLen)===$group)
		{
			$fieldKey = substr($key,$groupLen);
			$fields[$key] = $fieldKey;
		}
	}
	return $fields;
}
/**
 * 将parse_url结果组合成为字符串
 * @param string $parsed_url
 * @return string
 */
function unparse_url($parsed_url)
{
	$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
	$host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
	$pass     = ($user || $pass) ? "$pass@" : '';
	$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
	return "$scheme$user$pass$host$port$path$query$fragment";
}
/**
 * 获取静态文件绝对路径
 * @param string $src
 * @return string
 */
function parseStatic($src)
{
	$str = substr($src,0,7);
	if('http://'===$str || 'https:/'===$str)
	{
		// 绝对地址
		return $src;
	}
	else
	{
		$staticPath = Config::get('@.PATH_STATIC','');
		if('/'!==substr($staticPath,-1,1))
		{
			$staticPath .= '/';
		}
		$str = substr($staticPath,0,7);
		if('http://'===$str || 'https:/'===$str)
		{
			// 静态文件是某域名下的
			return $staticPath . $src;
		}
		else
		{
			// 静态文件是网站根目录下的
			return Request::getHome($staticPath . $src);
		}
	}
}
/**
 * 移出数组中数字键的成员
 * @param array &$array
 */
function removeArrayKeyNumeric(&$array)
{
	$s = count($array)+1;
	for($i=0;$i<=$s;++$i)
	{
		unset($array[$i]);
	}
}
/**
 * 处理多行文本，替换使用指定换行符换行
 * @param string $text
 * @param string $newLineSplit
 * @return string
 */
function parseMuiltLine($text,$newLineSplit = PHP_EOL)
{
	return str_replace("\n",PHP_EOL,str_replace("\n\n", "\n", str_replace("\r", "\n", $text)));
}
/**
 * 处理自动加载路径
 * @param string $rule
 * @param string $class
 * @param string $word
 * @return string
 */
function parseAutoloadPath($rule,$class = '',$word = '')
{
	return str_replace('%word', $word, str_replace('%class', $class, $rule));
}