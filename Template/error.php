<?php defined('YURUN_VERSION') or exit;?>
<!DOCTYPE>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>错误</title>
</head>

<body>
	<h1>错误信息：<?php echo $error['message'];?></h1>
	<p>
		<b>所在文件：</b><?php echo $error['file'];?></p>
	<p>
		<b>所在行数：</b><?php echo $error['line'];?></p>
	<p>YurunPHP <?php echo YURUN_VERSION;?></p>
</body>
</html>