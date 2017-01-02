<?php
return array (
		// 核心配置，擅改或覆盖同名配置项可能会造成网站无法访问
		'CORE_CLASSES' => array ('Control','Model','View',		// 核心类
		'ArrayData','Lang','Driver','Dispatch','Request','Event','Validator','Response','Cookie','Session'),
		'CORE_REQUIRE' => array ('ArrayData','Control','Dispatch','Request','Driver','Task'),		// 必须加载的核心类
		'CORE_DRIVER_CLASSES' => array ('Cache','Config','Db','Log'),
		'CORE_DRIVER_REQUIRE' => array (
				'Config/ConfigBase',		// 核心驱动类
				'Config/Config','Config/ConfigPhp'
		),		// 必须加载的核心驱动类
		'CORE_FUNCTIONS' => array ('common'),		// 核心函数库们
		                                      
		// 模块
		'MODULE_ON' => true,		// 是否开启模块功能
		'MODULE_NAME' => 'm',		// 模块参数名
		'MODULE_FOLDER' => 'Module',		// 模块文件夹名称
		'MODULE_DEFAULT' => 'Home',		// 默认模块名
		'MODULE_TEMPLATE'=>	true,	// 使用模块功能时，模版是否存放在模块中。为false时存放在APP_TEMPLATE中
		                             
		// 控制器
		'CONTROL_NAME' => 'c',		// 控制器参数名
		'CONTROL_DEFAULT' => 'Index',		// 默认控制器名
		'CONTROL_FOLDER' => 'Control',		// 控制器文件夹名称
		                               
		// 动作
		'ACTION_NAME' => 'a',		// 动作参数名
		'ACTION_DEFAULT' => 'index',		// 默认参数名
		                             
		// 模型
		'MODEL_FOLDER'			=> 'Model',		// 模型文件夹名称
		'MODEL_AUTO_FIELDS'		=>	true,		// 模型是否自动获取字段信息
		'MODEL_FIELDS_CACHE'	=>	true,		// 是否对模型字段缓存
		                           
		// 模版
		'TEMPLATE_FOLDER' => 'Template',		// 模版文件夹名称
		'TEMPLATE_EXT' => '.php',		// 模版扩展名
		
		// 组件
		'COMPONENT_EXT' => '.html',		// 组件模版扩展名
		                          
		// 主题
		'THEME_ON' => false,		// 是否开启主题功能
		'THEME' => 'Default',		// 默认主题
		                              
		// 类库
		'LIB_FOLDER' => 'Lib',		// 类库文件夹名称
		'LIB_DRIVER_FOLDER' => 'Driver',		// 驱动扩展文件夹名称
		                                 
		// 配置
		'CONFIG_FOLDER' => 'Config',		// 配置文件夹名称
		                             
		// 缓存
		'CACHE_FOLDER' => 'Cache',		// 缓存文件夹名称
		'CACHE_TEMPLATE_FOLDER' => 'Template',		// 模版缓存文件夹名称
		'CACHE_DATA_FOLDER' => 'Data',		// 数据缓存文件夹名称
		'CACHE_PAGE_FOLDER' => 'Page',		// 页面缓存文件夹名称
		'CACHE_EXT'			=> '.php',		// 缓存文件扩展名
		
		'LANG_FOLDER' => 'Lang',		// 语言包目录名
		'LANG_AUTO' => true,		// 自动识别语言
		                     
		// 默认
		'DEFAULT_FILTER' => 'htmlspecialchars',		// 默认过滤方法，支持数组实现多个
		'DEFAULT_LANG' => 'zh-cn',		// 默认语言
		
		'PLUGIN_FOLDER' => 'Plugin',		// 插件目录
		
		'TIMEZONE' => 'Asia/Shanghai',		// 时区设置
		
		'DB_PREFIX' => 'tb_',			// 默认表前缀
		'DB_DEFAULT_TYPE' => 'Mysql',	// 默认数据库类型
		
		'ERROR_DEBUG_TEMPLATE'	=>	PATH_TEMPLATE.'error.php',			// 错误异常模版，调试时使用
		'ERROR_RELEASE_TEMPLATE'=>	PATH_TEMPLATE.'error_release.php',	// 错误异常模版，正式运行时使用
		'ERROR_URL'				=>	'',									// 错误异常跳转页，非调试状态下使用
		
		// 日志
		'LOG_ON'			=>	true,				// 是否开启日志记录功能
		'LOG_TYPE'			=>	'file',				// 日志存储类型
		'LOG_MAX_SIZE'		=>	104857600,			// 单个日志文件最大大小
		'LOG_ERROR'			=>	true,				// 是否记录PHP异常和错误
		'LOG_DATE_FORMAT'	=>	'Y-m-d H:i:s',		// 日志中显示的日期时间格式
		'LOG_PATH'			=>	ROOT_PATH.'Logs/',	// 日志文件保存路径
		'LOG_EXT'			=>	'.log',				// 日志文件扩展名
		
		// URL配置
		'URL_PROTOCOL'				=>	'',				// URL的协议，留空则取当前协议。可取值http://和https://
		'PATHINFO_ON'				=>	true,			// 是否开启支持PATHINFO。格式：index.php/Module/Control/action
		'URL_PARSE_ON'				=>	true,			// 是否开启URL路由解析支持，需要伪静态规则支持。格式：/Module/Control/action
		'QUERY_PATHINFO_ON'			=>	true,			// 是否开启支持参数传入URL路由解析。
		'PATHINFO_QUERY_NAME'		=>	'i',			// 持参数传入URL路由解析查询参数名。格式：i=Module/Control/action
		'FILTER_DOMAIN'				=>	false,			// 是否过滤非DOMAIN配置项域名的访问，如果DOMAIN不配置或为空则本项无效
		
		// 控制器returnData方法默认返回数据格式类型
		'CONTROL_RETURN_TYPE'		=>	'json',
		// 自定义分层
		'CUSTOM_LAYER'		=>	array(),
		'TEMPLATE_ENGINE_ON'		=>	true,
		'TEMPLATE_CACHE_ON'			=>	true,
		'TEMPLATE_CACHE_EXPIRE'		=>	0,
		'TEMPLATE_ENGINE'			=>	'YurunTPEView',
		'TEMPLATE_TAG_LEFT'			=>	'<',
		'TEMPLATE_TAG_RIGHT'		=>	'>',
		'TEMPLATE_ECHO_VAR_TAG_LEFT'		=>	'<%=',
		'TEMPLATE_ECHO_VAR_TAG_RIGHT'		=>	'%>',
		'TEMPLATE_ECHO_CONST_TAG_LEFT'		=>	'<CONST:',
		'TEMPLATE_ECHO_CONST_TAG_RIGHT'		=>	'>',
		'TEMPLATE_ECHO_CONFIG_TAG_LEFT'		=>	'<CONFIG:',
		'TEMPLATE_ECHO_CONFIG_TAG_RIGHT'	=>	'>',
		'TEMPLATE_ECHO_LANG_TAG_LEFT'		=>	'<LANG:',
		'TEMPLATE_ECHO_LANG_TAG_RIGHT'	=>	'>',
		
		// 静态文件路径
		'PATH_STATIC' => 'Static'
);
