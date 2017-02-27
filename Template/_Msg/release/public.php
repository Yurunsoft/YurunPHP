<?php defined('YURUN_START') or exit;?><!DOCTYPE>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
* {
	margin: 0
}
</style>
<title>YurunPHP 友情提醒</title>
<script>
function redirect()
{
<?php
if (null === $url)
{
	?>
	history.go(-1);
<?php
}
else
{
	?>
	location='<?php echo $url;?>';
<?php
}
?>
}
setTimeout('redirect()',1000);
</script>
</head>
<body>
	<center>
		<br><div
				style="width: 450px; padding: 0px; border: 1px solid #DADADA;">
				<div
					style="padding: 6px; font-size: 12px; border-bottom: 1px solid #DADADA;">
					<b>YurunPHP 友情提醒</b>
				</div>
				<div style="height: 130px; font-size: 10pt; background: #ffffff">
					<br><br><?php echo $msg;?><br><br><a
									href="<?php if(null===$url) echo $url;else echo 'javascript:redirect()';?>">如果你的浏览器没反应，请点击这里...</a><br>
				
				</div>
			</div>
	
	</center>
</body>
</html>