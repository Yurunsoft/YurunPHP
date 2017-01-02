<?php
/**
 * YurunPHP 开发框架 编译代码
 * @author Yurun <admin@yurunsoft.com>
 */
// 定义编译状态
define('COMPILE', true);
// 引入框架
require_once 'Yurun.php';
$result = '';
// 引入核心重要文件
foreach ($GLOBALS['cfg']['CORE_REQUIRE'] as $value)
{
	$result .= includeFile(PATH_CORE . "{$value}.class.php");
}
$arr = array_diff($GLOBALS['cfg']['CORE_CLASSES'], $GLOBALS['cfg']['CORE_REQUIRE']);
// 公共函数库
$result .= includeFile(PATH_CORE . 'Functions/common.php');
// 引入核心驱动
$result .= includeFile(PATH_CORE_DRIVER . "Config/ConfigBase.class.php");
$result .= includeFile(PATH_CORE_DRIVER . 'Config/ConfigPhp.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Config/Config.class.php');
$result .= includeFile(PATH_CORE_DRIVER . "Cache/CacheBase.class.php");
$result .= includeFile(PATH_CORE_DRIVER . 'Cache/CacheFile.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Cache/Cache.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Log/LogBase.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Log/LogFile.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Log/Log.class.php');
$result .= includeFile(PATH_CORE_DRIVER . "Db/DbBase.class.php");
$result .= includeFile(PATH_CORE_DRIVER . 'Db/DbMysql.class.php');
$result .= includeFile(PATH_CORE_DRIVER . 'Db/Db.class.php');
// 引入核心其它文件
foreach ($arr as $value)
{
	$result .= includeFile(PATH_CORE . "{$value}.class.php");
}
// 定义已编译状态
$fc = strip_whitespace(file_get_contents('Yurun.php'));
$fc = substr($fc, 5);
$fc = "<?php define('IS_COMPILED',true);{$fc}";
// 写出文件
file_put_contents('Yurun-min.php', str_replace('// {compile}', $result, $fc),LOCK_EX);
header('Content-type: text/html; charset=utf-8');
echo '生成成功！';
/**
 * 将PHP文件读入并去除空格和注释
 *
 * @param type $file        	
 * @return type
 */
function includeFile($file)
{
	return substr(strip_whitespace(file_get_contents($file)), 5);
}

/**
 * 去除代码中的空白和注释
 *
 * @param string $content
 *        	代码内容
 * @return string
 */
function strip_whitespace($content)
{
	$stripStr = '';
	// 分析php源码
	$tokens = token_get_all($content);
	$last_space = false;
	for ($i = 0, $j = count($tokens); $i < $j; $i ++)
	{
		if (is_string($tokens[$i]))
		{
			$last_space = false;
			$stripStr .= $tokens[$i];
		}
		else
		{
			switch ($tokens[$i][0])
			{
				// 过滤各种PHP注释
				case T_COMMENT :
				case T_DOC_COMMENT :
					if (stripos($tokens[$i][1], '{compile}') !== false)
					{
						$stripStr .= "// {compile}\n";
					}
					break;
				// 过滤空格
				case T_WHITESPACE :
					if (! $last_space)
					{
						$stripStr .= ' ';
						$last_space = true;
					}
					break;
				case T_START_HEREDOC :
					$stripStr .= "<<<YURUN\n";
					break;
				case T_END_HEREDOC :
					$stripStr .= "YURUN;\n";
					for ($k = $i + 1; $k < $j; $k ++)
					{
						if (is_string($tokens[$k]) && $tokens[$k] === ';')
						{
							$i = $k;
							break;
						}
						else if ($tokens[$k][0] === T_CLOSE_TAG)
						{
							break;
						}
					}
					break;
				default :
					$last_space = false;
					$stripStr .= $tokens[$i][1];
			}
		}
	}
	return $stripStr;
}