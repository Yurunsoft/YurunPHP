<?php
/**
 * YurunPHP 开发框架 编译代码
 * @author Yurun <yurun@yurunsoft.com>
 */
$result = '';
$coreClasses = array('ArrayData');
foreach($coreClasses as $class)
{
	$result .= includeFile(__DIR__ . '/Core/'. $class .'.class.php');
}
// 加载Core目录文件
$dir = dir(__DIR__ . '/Core/');
while (false !== ($file = $dir->read()))
{
	if ('.' !== $file && '..' !== $file)
	{
		$fileName = __DIR__ . '/Core/' . $file;
		if(!is_dir($fileName) && !in_array(basename($file,'.class.php'),$coreClasses))
		{
			$result .= includeFile(__DIR__ . '/Core/' . $file);
		}
	}
}
$result .= includeFile(__DIR__ . '/Core/Traits/TLinkOperation.trait.php');
$dir->close();
// 加载驱动
$dir = dir(__DIR__ . '/Core/Driver/');
while (false !== ($file = $dir->read()))
{
	if ('.' !== $file && '..' !== $file)
	{
		$fileName = __DIR__ . '/Core/Driver/' . $file;
		if(is_dir($fileName))
		{
			if('Db' === $file)
			{
				$baseFileName = $file . 'PDOBase.class.php';
			}
			else
			{
				$baseFileName = $file . 'Base.class.php';
			}
			$baseFileNames = array($baseFileName);
			$result .= includeFile($fileName . '/' . $baseFileName);
			switch($file)
			{
				case 'Config':
					$tfile = $file . 'FileBase.class.php';
					$baseFileNames[] = $tfile;
					$result .= includeFile($fileName . '/' . $tfile);
					break;
			}
			$dir2 = dir($fileName . '/');
			while (false !== ($file2 = $dir2->read()))
			{
				if ('.' !== $file2 && '..' !== $file2 && !in_array($file2, $baseFileNames))
				{
					$result .= includeFile($fileName . '/' . $file2);
				}
			}
			$dir2->close();
		}
	}
}
$dir->close();
$yurunContent = strip_whitespace(file_get_contents('Yurun.php'));
$yurunContent = substr($yurunContent, 5);
$result = '<?php define(\'IS_COMPILE\',true);' . $result . $yurunContent;
file_put_contents('Yurun-min.php', $result,LOCK_EX);
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