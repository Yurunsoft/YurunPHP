<?php
/**
 * 响应类
 * @author Yurun <admin@yurunsoft.com>
 */
class Response
{
	/**
	 * 设置HTTP状态码
	 * 所有消息值和文本均来自于百度百科，HTTP状态码http://baike.baidu.com/view/1790469.htm
	 *
	 * @access public static
	 * @param int $status
	 *        	状态码值，如：301
	 * @return string
	 */
	public static function status($status, $msg = null)
	{
		static $httpStatus = array (
				// 消息（1字头）
				100 => 'Continue',101 => 'Switching Protocols',102 => 'Processing',
				// 成功（2字头）
				200 => 'OK',201 => 'Created',202 => 'Accepted',203 => 'Non-Authoritative Information',204 => 'No Content',205 => 'Reset Content',206 => 'Partial Content',207 => 'Multi-Status',
				// 重定向（3字头）
				300 => 'Multiple Choices',301 => 'Moved Permanently',302 => 'Temporarily Moved',303 => 'See Other',304 => 'Not Modified',305 => 'Use Proxy',306 => 'Switch Proxy',				// 在最新版的规范中，306状态码已经不再被使用
				307 => 'Temporary Redirect',
				// 请求错误（4字头）
				400 => 'Bad Request',401 => 'Unauthorized',402 => 'Payment Required',403 => 'Forbidden',404 => 'Not Found',405 => 'Method Not Allowed',406 => 'Not Acceptable',407 => 'Proxy Authentication Required',408 => 'Request Timeout',409 => 'Conflict',410 => 'Gone',411 => 'Length Required',412 => 'Precondition Failed',413 => 'Request Entity Too Large',414 => 'Request-URI Too Long',415 => 'Unsupported Media Type',416 => 'Requested Range Not Satisfiable',417 => 'Expectation Failed',421 => 'There are too many connections from your internet address',422 => 'Unprocessable Entity',423 => 'Locked',424 => 'Failed Dependency',425 => 'Unordered Collection',426 => 'Upgrade Required',449 => 'Retry With',				// 由微软扩展，代表请求应当在执行完适当的操作后进行重试。
				                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   // 服务器错误（5字头）
				500 => 'Internal Server Error',501 => 'Not Implemented',502 => 'Bad Gateway',503 => 'Service Unavailable',504 => 'Gateway Timeout',505 => 'HTTP Version Not Supported',506 => 'Variant Also Negotiates',507 => 'Insufficient Storage',508 => 'Loop Detected',509 => 'Bandwidth Limit Exceeded',510 => 'Not Extended',600 => 'Unparseable Response Headers');
		if ($msg === null)
		{
			if (isset($httpStatus[$status]))
			{
				$msg = $httpStatus[$status];
			}
			else
			{
				$msg = '';
			}
		}
		header("HTTP/1.1 {$status} {$msg}");
		// 保证FastCGI模式下正常
		header("Status:{$status} {$msg}");
	}
	
	/**
	 * 输出消息，支持模版和设置http状态码
	 *
	 * @param string $msg        	
	 * @param int $status        	
	 */
	public static function msg($msg, $url = null, $status = 200)
	{
		// 设置http状态码
		self::status($status);
		$ext = Config::get('@.TEMPLATE_EXT');
		// 项目模版目录下对应http状态的模版文件
		$file = APP_TEMPLATE . "_Msg/{$status}{$ext}";
		if (is_file($file))
		{
			include $file;
		}
		else
		{
			// 项目模版目录下公用消息模版
			$file = APP_TEMPLATE . "_Msg/public{$ext}";
			if (is_file($file))
			{
				include $file;
			}
			else
			{
				// 框架模版
				$file=PATH_TEMPLATE . "_Msg/public$ext";
				if(is_file($file))
				{
					include $file;
				}
				else
				{// 所有模版都不存在，只能手动输出默认的啦
					// 设定utf-8编码，防止乱码
					header('Content-type: text/html; charset=utf-8');
					if(empty($url))
					{
						// 页面后退
						$js='history.go(-1);';
					}
					else
					{
						// 跳转
						$js="location='{$url}';";
					}
					// 输出并结束脚本
					exit(
<<<EOF
{$msg}
<script>
function redirect()
{
	{$js};
}
setTimeout('redirect()',1000);
</script>
EOF
);
				}
			}
		}
		exit;
	}
	
	/**
	 * 跳转
	 *
	 * @param string $url        	
	 * @param int $status        	
	 */
	public static function redirect($url, $status = 301)
	{
		// 设置http状态码
		self::status($status);
		// 跳转
		header("Location:{$url}");
	}
}