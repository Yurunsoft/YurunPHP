<?php
/**
 * 响应类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class Response
{
	/**
	 * 设置HTTP状态码
	 * 所有消息值和文本均来自于百度百科，HTTP状态码http://baike.baidu.com/view/1790469.htm
	 * @access public static
	 * @param int $status 状态码值，如：301
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
		if (null === $msg)
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
		header('HTTP/1.1 ' . $status . $msg);
		// 保证FastCGI模式下正常
		header('Status:' . $status . $msg);
	}
	
	/**
	 * 输出消息，支持模版和设置http状态码
	 * @param string $msg        	
	 * @param int $status        	
	 */
	public static function msg($msg, $url = null, $status = 200, $data = array())
	{
		if(IS_CLI)
		{
			echo $msg;
		}
		else
		{
			// 设置http状态码
			self::status($status);
			ob_end_clean();
			$ext = Config::get('@.TEMPLATE_EXT');
			$path = '_Msg/' . (IS_DEBUG ? 'debug/' : 'release/');
			// 项目模版目录下对应http状态的模版文件
			$file = APP_TEMPLATE . $path . $status . $ext;
			if (is_file($file))
			{
				include $file;
				exit;
			}
			$file = PATH_TEMPLATE . $path . $status . $ext;
			if (is_file($file))
			{
				include $file;
				exit;
			}
			// 项目模版目录下公用消息模版
			$file = APP_TEMPLATE . $path . 'public' . $ext;
			if (is_file($file))
			{
				include $file;
				exit;
			}
			// 框架模版
			$file = PATH_TEMPLATE . $path . 'public' . $ext;
			if(is_file($file))
			{
				include $file;
				exit;
			}
			else
			{
				// 所有模版都不存在，只能手动输出默认的啦
				// 设定utf-8编码，防止乱码
				header('Content-type: text/html; charset=utf-8');
				// 输出并结束脚本
				exit(
<<<JS
{$msg}
JS
);
			}
		}
		exit;
	}
	
	/**
	 * 跳转
	 * 直接URL
	 * @param string $url        	
	 * @param int $status        	
	 */
	public static function redirect($url, $status = 301)
	{
		// 设置http状态码
		self::status($status);
		// 跳转
		header('Location:' . $url);
		exit;
	}
	
	/**
	 * 跳转
	 * 快捷URL
	 * @param string $url
	 * @param int $status
	 */
	public static function redirectU($url, $status = 301,$param=array())
	{
		// 设置http状态码
		self::status($status);
		// 跳转
		header('Location:'.Dispatch::url($url,$param));
		exit;
	}
	/**
	 * 获取MIME，成功返回mime，失败返回false
	 * @access public static
	 * @param string $ext 扩展名，如html。如果作为文件下载，可以传入download
	 * @return string
	 */
	public static function setMime($ext)
	{
		static $mimes = array(
				'download' => 'application/force-download',
				'ez' => 'application/andrew-inset',
				'hqx' => 'application/mac-binhex40',
				'cpt' => 'application/mac-compactpro',
				'doc' => 'application/msword',
				'bin' => 'application/octet-stream',
				'dms' => 'application/octet-stream',
				'lha' => 'application/octet-stream',
				'lzh' => 'application/octet-stream',
				'exe' => 'application/octet-stream',
				'class' => 'application/octet-stream',
				'so' => 'application/octet-stream',
				'dll' => 'application/octet-stream',
				'oda' => 'application/oda',
				'pdf' => 'application/pdf',
				'ai' => 'application/postscript',
				'eps' => 'application/postscript',
				'ps' => 'application/postscript',
				'smi' => 'application/smil',
				'smil' => 'application/smil',
				'mif' => 'application/vnd.mif',
				'xls' => 'application/vnd.ms-excel',
				'ppt' => 'application/vnd.ms-powerpoint',
				'wbxml' => 'application/vnd.wap.wbxml',
				'wmlc' => 'application/vnd.wap.wmlc',
				'wmlsc' => 'application/vnd.wap.wmlscriptc',
				'bcpio' => 'application/x-bcpio',
				'vcd' => 'application/x-cdlink',
				'pgn' => 'application/x-chess-pgn',
				'cpio' => 'application/x-cpio',
				'csh' => 'application/x-csh',
				'dcr' => 'application/x-director',
				'dir' => 'application/x-director',
				'dxr' => 'application/x-director',
				'dvi' => 'application/x-dvi',
				'spl' => 'application/x-futuresplash',
				'gtar' => 'application/x-gtar',
				'hdf' => 'application/x-hdf',
				'js' => 'application/x-javascript',
				'skp' => 'application/x-koan',
				'skd' => 'application/x-koan',
				'skt' => 'application/x-koan',
				'skm' => 'application/x-koan',
				'latex' => 'application/x-latex',
				'nc' => 'application/x-netcdf',
				'cdf' => 'application/x-netcdf',
				'sh' => 'application/x-sh',
				'shar' => 'application/x-shar',
				'swf' => 'application/x-shockwave-flash',
				'sit' => 'application/x-stuffit',
				'sv4cpio' => 'application/x-sv4cpio',
				'sv4crc' => 'application/x-sv4crc',
				'tar' => 'application/x-tar',
				'tcl' => 'application/x-tcl',
				'tex' => 'application/x-tex',
				'texinfo' => 'application/x-texinfo',
				'texi' => 'application/x-texinfo',
				't' => 'application/x-troff',
				'tr' => 'application/x-troff',
				'roff' => 'application/x-troff',
				'man' => 'application/x-troff-man',
				'me' => 'application/x-troff-me',
				'ms' => 'application/x-troff-ms',
				'ustar' => 'application/x-ustar',
				'src' => 'application/x-wais-source',
				'xhtml' => 'application/xhtml+xml',
				'xht' => 'application/xhtml+xml',
				'zip' => 'application/zip',
				'au' => 'audio/basic',
				'snd' => 'audio/basic',
				'mid' => 'audio/midi',
				'midi' => 'audio/midi',
				'kar' => 'audio/midi',
				'mpga' => 'audio/mpeg',
				'mp2' => 'audio/mpeg',
				'mp3' => 'audio/mpeg',
				'aif' => 'audio/x-aiff',
				'aiff' => 'audio/x-aiff',
				'aifc' => 'audio/x-aiff',
				'm3u' => 'audio/x-mpegurl',
				'ram' => 'audio/x-pn-realaudio',
				'rm' => 'audio/x-pn-realaudio',
				'rpm' => 'audio/x-pn-realaudio-plugin',
				'ra' => 'audio/x-realaudio',
				'wav' => 'audio/x-wav',
				'pdb' => 'chemical/x-pdb',
				'xyz' => 'chemical/x-xyz',
				'bmp' => 'image/bmp',
				'gif' => 'image/gif',
				'ief' => 'image/ief',
				'jpeg' => 'image/jpeg',
				'jpg' => 'image/jpeg',
				'jpe' => 'image/jpeg',
				'png' => 'image/png',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'djvu' => 'image/vnd.djvu',
				'djv' => 'image/vnd.djvu',
				'wbmp' => 'image/vnd.wap.wbmp',
				'ras' => 'image/x-cmu-raster',
				'pnm' => 'image/x-portable-anymap',
				'pbm' => 'image/x-portable-bitmap',
				'pgm' => 'image/x-portable-graymap',
				'ppm' => 'image/x-portable-pixmap',
				'rgb' => 'image/x-rgb',
				'xbm' => 'image/x-xbitmap',
				'xpm' => 'image/x-xpixmap',
				'xwd' => 'image/x-xwindowdump',
				'igs' => 'model/iges',
				'iges' => 'model/iges',
				'msh' => 'model/mesh',
				'mesh' => 'model/mesh',
				'silo' => 'model/mesh',
				'wrl' => 'model/vrml',
				'vrml' => 'model/vrml',
				'css' => 'text/css',
				'html' => 'text/html',
				'htm' => 'text/html',
				'asc' => 'text/plain',
				'txt' => 'text/plain',
				'rtx' => 'text/richtext',
				'rtf' => 'text/rtf',
				'sgml' => 'text/sgml',
				'sgm' => 'text/sgml',
				'tsv' => 'text/tab-separated-values',
				'wml' => 'text/vnd.wap.wml',
				'wmls' => 'text/vnd.wap.wmlscript',
				'etx' => 'text/x-setext',
				'xsl' => 'text/xml',
				'xml' => 'text/xml',
				'mpeg' => 'video/mpeg',
				'mpg' => 'video/mpeg',
				'mpe' => 'video/mpeg',
				'qt' => 'video/quicktime',
				'mov' => 'video/quicktime',
				'mxu' => 'video/vnd.mpegurl',
				'avi' => 'video/x-msvideo',
				'movie' => 'video/x-sgi-movie',
				'ice' => 'x-conference/x-cooltalk',
				'json' => 'application/json',
		);
		header('Content-type: ' . $mimes[$ext] . ';charset=utf-8');
	}
	/**
	 * 设置下载文件的文件名
	 * @param string $fileName
	 */
	public static function setDownFile($fileName)
	{
		header('Content-Disposition: attachment; filename=' . $fileName);
	}
}