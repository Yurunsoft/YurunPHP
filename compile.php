<?php
/**
 * YurunPHP 开发框架 编译代码
 * @author Yurun <yurun@yurunsoft.com>
 */
$result = '';
enumFiles(__DIR__ . '/Core/',function($fileName)use(&$result){
	$result .= includeFile($fileName);
});
$yurunContent = strip_whitespace(file_get_contents('Yurun.php'));
$yurunContent = substr($yurunContent, 5);
$result = '<?php define(\'IS_COMPILE\',true);' . $result . $yurunContent;
file_put_contents('Yurun-min.php', $result,LOCK_EX);
header('Content-type: text/html; charset=utf-8');
echo '生成成功！';
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
					$stripStr .= "YURUN\n";
					break;
				default :
					$last_space = false;
					$stripStr .= $tokens[$i][1];
			}
		}
	}
	return $stripStr;
}