<?php defined('YURUN_VERSION') or exit;?>
<!DOCTYPE>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>错误</title>
</head>

<body>
	<h1>错误信息：<?php echo $error['message'];?></h1>
	<p><b>文件：</b><?php echo $error['file'];?></p>
	<p><b>行数：</b><?php echo $error['line'];?></p>
	<p><b>跟踪：</b><br/><?php echo nl2br($error['trace']);?></p>
	<?php if(isset($error['lastsql'])){?>
	<p><b>最后执行的SQL语句：</b><?php echo $error['lastsql'];?></p>
	<?php }?>
	<p>YurunPHP <?php echo YURUN_VERSION;?></p>
</body>
</html>