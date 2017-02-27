<?php defined('YURUN_START') or exit;?><!doctype html><html>
<head>
	<title><%=$serviceName%>::<%=$methodName%>()测试 - WebService</title>
	<script src="//cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
	<link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css"/>
	<script src="//cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<style>
	a.Method {
		color:#FF5722;
		text-decoration: underline;
	}
	a.Method:hover {
		color:#F7B824
	}
	.VarType {
		color:#1E9FFF
	}
	.Var {
		color:#393D49
	}
	.MethodList {
		list-style:none;
	}
	.MethodList > li {
	}
	.footer{
		text-align:center;
		padding-top: 1rem;
	}
	</style>
</head>
<body>
	<div class="container-fluid">
		<h1>
			<a href="<%=$indexUrl%>"><%=$serviceName%></a>
			<small><a href="<%=$wsdlUrl%>" target="_blank">服务说明(wsdl文件)</a></small>
		</h1>
		<h3>
			<%=$methodName%>
			<small>测试</small>
		</h3>
		<form id="form1">
			<table class="table table-bordered table-striped table-hover">
				<thead>
					<th width="150">参数</th>
					<th>值</th>
				</thead>
				<tboty>
					<foreach list="$method['params']" key="paramName" value="param">
					<tr>
						<td class="Var"><%=$paramName%></td>
						<td>
							<textarea class="form-control" name="<%=$paramName%>" rows="3"></textarea>
						</td>
					</tr>
					</foreach>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2">
							<button class="btn btn-primary">调用测试</button>
						</td>
					</tr>
					<tr>
						<td class="Var">返回结果</td>
						<td>
							<textarea id="TextareaResult" class="form-control" readonly rows="10"></textarea>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
	</div>
	<footer class="footer navbar navbar-default">
        <div class="container">
            <p>Powered by <a href="http://www.yurunphp.com" target="_blank">YurunPHP</a> <%=Yurun::YURUN_VERSION%></p>
        </div>
    </footer>
	<script>
	$('#form1').submit(function(){
		$.ajax({
			type: "post",
			url: $('#form1').attr('action'), 
			data: $(this).serialize(),
			success: function(data) {
				if(void 0 === data.success)
				{
					$('#TextareaResult').val('服务器错误');
				}
				else if(data.success)
				{
					$('#TextareaResult').val(data.result);
				}
				else
				{
					$('#TextareaResult').val(data.message);
				}
			},
			error: function(error){
				$('#TextareaResult').val('服务器错误');
			}
		});
		return false;
	});
	</script>
</body>
</html>