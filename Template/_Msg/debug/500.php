<?php defined('YURUN_START') or exit;?>
<!DOCTYPE>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>错误</title>
<style>
.code{
	border-spacing:0;
	border-collapse:collapse;
	min-width: 800px;
	max-width: 100%;
}
.code tr
{
	background-color: #fff;
	color: #686868;
}
.code thead th:nth-child(1)
{
	border-right:1px solid #aaa;
}
.code tbody td:nth-child(1)
{
	border-right:1px solid #ddd;
}
.code tbody tr:nth-child(2n)
{
	background-color: #f4f4f4;
}
.code tbody tr:hover
{
	background-color:#e5fde1
}
.code th,.code td
{
	color: #333;
	padding: 10px;
	text-align:left;
}
.code th > input[type=checkbox]
{
	vertical-align: sub;
}
.code th{
	color: #fff;
	background-color: #353535;
}
.currline{
	background:#E74856 !important;
}
.currline td{
	color:#fff !important;
}
.currline td:nth-child(1){
	border-right:1px solid #D13438;
}
.code{
	font-family: Monaco,Menlo,Consolas,"Courier New",monospace;
}
.code .rb{
	border-right:1px solid #eee;
}
</style>
</head>

<body>
	<h1>错误信息：<?php echo nl2br($data['message']);?></h1>
	<p><b>文件：</b><?php echo $data['file'];?> <b>行数：</b><?php echo $data['line'];?></p>
	<p><b>跟踪：</b><br/><?php echo nl2br($data['trace']);?></p>
	<?php if(isset($data['lastsql'])):?>
	<p><b>最后执行的SQL语句：</b><?php echo $data['lastsql'];?></p>
	<?php endif;?>
	<p><b>代码追踪：</b></p>
	<table class="code">
		<thead>
			<th style="text-align:center;">行号</th>
			<th>代码</th>
		</thead>
		<tbody>
			<?php foreach(getErrorFileCode($data['file'], $data['line']) as $row):?>
			<tr<?php if($row['is_error_line']):?> class="currline"<?php endif;?>>
				<td style="text-align:center"><?php echo $row['line'];?></td>
				<td><?php echo $row['content'];?></td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
	<p>YurunPHP <?php echo Yurun::YURUN_VERSION;?></p>
</body>
</html>