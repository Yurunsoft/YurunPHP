<?php defined('YURUN_START') or exit;?><!doctype html><html>
<head>
	<title><%=$serviceName%> - WebService</title>
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
			<%=$serviceName%>
			<small><a href="<%=$wsdlUrl%>" target="_blank">服务说明(wsdl文件)</a></small>
		</h1>
		<blockquote>
			<%=$service['description']%>
		</blockquote>
		<ul class="MethodList">
		<foreach list="$service['methods']" key="methodName" value="method">
			<li>
				<h3>
					<span class="VarType"><%=$method['return']['type']%></span>
					<span>
					<a href="<url=''.$testMCA,array('serviceName'=>$serviceName,'methodName'=>$methodName)/>" class="Method" target="_blank"><%=$methodName%></a>
					(
						<php>
							$count = count($method['params']);
						</php>
						<foreach list="$method['params']" key="paramName" value="param">
							<span class="VarType"><%=$param['type']%></span>
							<span class="Var"><%=$paramName%></span>
							<if condition="$index < $count - 1">,</if>
						</foreach>
					)</span>
				</h3>
				<blockquote>
					<%=$method['comment']%>
				</blockquote>
				<table class="table table-bordered table-striped table-hover">
					<thead>
						<th width="150">参数</th>
						<th>描述</th>
					</thead>
					<tboty>
						<foreach list="$method['params']" key="paramName" value="param">
						<tr>
							<td class="Var"><%=$paramName%></td>
							<td><%=$param['comment']%></td>
						</tr>
						</foreach>
					</tbody>
				</table>
			</li>
		</foreach>
		</ul>
	</div>
	<footer class="footer navbar navbar-default">
        <div class="container">
            <p>Powered by <a href="http://www.yurunphp.com" target="_blank">YurunPHP</a> <%=Yurun::YURUN_VERSION%></p>
        </div>
    </footer>
</body>
</html>