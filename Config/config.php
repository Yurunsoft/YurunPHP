<?php
return array (
	// 项目配置文件
	'APP_CONFIG'	=>	array(
		'App'	=>	array(
			'type'		=>	'PHP',
			'option'	=>	array(
				'filename'	=>	'config.php',
			),
			'autoload'	=>	true,
		),
		'AppRuntime'	=>	array(
			'type'		=>	'PHP',
			'option'	=>	array(
				'filename'	=>	'' . (IS_DEBUG ? 'debug.php' : 'release.php'),
			),
			'autoload'	=>	true,
		),
		'Route'	=>	array(
			'type'		=>	'PHP',
			'option'	=>	array(
				'filename'	=>	'route.php',
			),
			'autoload'	=>	true,
		),
	),
	
	// 自定义分层
	'YURUN_LAYERS' => array(
		'Model',
		'View',
		'Control'
	),
	
	// 插件驱动配置（配置驱动）
	'PLUGIN_OPTION'	=>	array(
		'type'		=>	'PHP',
		'option'	=>	array(
			'filename'	=>	'plugin.php',
		),
	),
	
	// 自动加载规则
	'AUTOLOAD_RULES'	=>	array(
		array('type'=>'FirstWord','word'=>'YC','path'=>'Ex/Component/%class'),
		array('type'=>'LastWord','word'=>'Component','path'=>'Ex/Component'),
		array('type'=>'Word','word'=>'IDb','path'=>'Core/Driver/Db','ext'=>'.implements.php'),
		array('type'=>'Word','word'=>'TDbOperation','path'=>'Core/Driver/Db','ext'=>'.trait.php'),
		array('type'=>'Word','word'=>'TDbSQLHelper','path'=>'Core/Driver/Db','ext'=>'.trait.php'),
		array('type'=>'Word','word'=>'TLinkOperation','path'=>'Core/Traits','ext'=>'.trait.php'),
		array('type'=>'Path','path'=>'Lib'),
		array('type'=>'Path','path'=>'Lib/%class'),
	),

	// import函数导入文件规则
	'IMPORT'	=>	array(
		// 'xxx'	=>	'文件路径'
	),
	
	// 自动开启Session功能
	'SESSION_AUTO_OPEN'	=>	true,
	// 自定义Session存储
	'SESSION_SAVE_HANDLER'	=>	'files',
	
	
	/**
	 * 模块部分
	 */
	// 模块参数名
	'MODULE_NAME' => 'm',
	// 模块文件夹名称
	'MODULE_PATH' => 'Module',
	// 默认模块名
	'MODULE_DEFAULT' => 'Home',
	// 使用模块功能时，模版是否存放在模块中。为false时存放在APP_TEMPLATE中
	'MODULE_TEMPLATE'=>	true,

	/**
	 * 控制器部分
	 */
	// 控制器参数名
	'CONTROL_NAME' => 'c',
	// 默认控制器名
	'CONTROL_DEFAULT' => 'Index',

	/**
	 * 动作部分
	 */
	// 动作参数名
	'ACTION_NAME' => 'a',
	// 默认参数名
	'ACTION_DEFAULT' => 'index',

	/**
	 * 模型部分
	 */
	// 模型是否自动获取字段信息
	'MODEL_AUTO_FIELDS'		=>	true,
	// 是否对模型字段缓存，为true则会缓存直到手动删除缓存才会更新
	'MODEL_FIELDS_CACHE'	=>	false,
	// 是否在变量中动态缓存模型字段缓存，好处是多次实例化不会重复处理表字段，坏处是占用内存
	'MODEL_DYNAMIC_FIELDS_CACHE'	=>	true,
	
	/**
	 * 数据库部分
	 */
	// 默认表前缀
	'DB_PREFIX' => 'tb_',

	/**
	 * 模版部分
	 */
	// 模版文件夹名称
	'TEMPLATE_PATH' => 'Template',
	// 模版扩展名
	'TEMPLATE_EXT' => '.php',
	// 模版缓存文件夹名称
	'TEMPLATE_CACHE_FOLDER' => 'Template',

	/**
	 * 组件部分
	 */
	// 组件模版扩展名
	'COMPONENT_EXT' => '.html',

	/**
	 * 主题部分
	 */
	// 是否开启主题功能
	'THEME_ON' => false,
	// 默认主题名称
	'THEME' => 'Default',

	/**
	 * 类库部分
	 */
	// 类库文件夹名称
	'LIB_PATH' => 'Lib',

	/**
	 * 配置部分
	 */
	// 配置文件夹名称
	'CONFIG_PATH' => 'Config',

	/**
	 * 缓存部分
	 */
	// 缓存文件夹路径
	'CACHE_PATH' => 'Cache',
	// 缓存文件扩展名
	'CACHE_EXT'			=> '.php',
	// 默认缓存驱动配置
	'APP_CACHE' => array(
		'DefaultFile' => array(
			'type' => 'File',
		)
	),
	// 默认缓存
	'DEFAULT_CACHE' => 'DefaultFile',

	/**
	 * 语言包部分
	 */
	// 语言包目录名
	'LANG_PATH' => 'Lang',
	// 自动识别语言
	'LANG_AUTO' => true,
	// 默认语言
	'LANG_DEFAULT' => 'zh-cn',

	/**
	 * 过滤相关
	 */
	// 默认过滤方法，支持数组实现多个
	'DEFAULT_FILTER' => 'htmlspecialchars',
	
	/**
	 * 插件相关
	 */
	// 插件目录
	'PLUGIN_PATH' => 'Plugin',

	

	/**
	 * 日志相关
	 */
	// 是否记录PHP异常和错误
	'LOG_ERROR'			=>	true,
	// 在命令行模式下是否每条日志都触发保存操作，对长时间运行的cli操作应该设置为true，否则不会在日志中看到记录
	'LOG_CLI_AUTOSAVE'	=>	false,
	// 日志路径
	'LOG_PATH'			=>	'Logs',
	// 日志驱动配置
	'APP_LOG'	=>	array(
		'DefaultFile'	=>	array(
			'type'		=>	'File',
			'option'	=>	array(
				'max_size'		=>	104857600,			// 单个日志文件最大大小
				'date_format'	=>	'Y-m-d H:i:s',		// 日志中显示的日期时间格式
				'ext'			=>	'.log',				// 日志文件扩展名
			),
		)
	),
	// 默认日志
	'DEFAULT_LOG'	=>	'DefaultFile',

	/**
	 * URL配置
	 */
	// URL的协议，留空则取当前协议。可取值http://和https://
	'URL_PROTOCOL'				=>	'',
	// 是否开启支持PATHINFO。格式：index.php/Module/Control/action
	'PATHINFO_ON'				=>	true,
	// 是否开启URL路由解析支持，需要伪静态规则支持。格式：/Module/Control/action
	'URL_PARSE_ON'				=>	true,
	// 是否开启支持参数传入URL路由解析。
	'QUERY_PATHINFO_ON'			=>	true,
	// 持参数传入URL路由解析查询参数名。格式：i=Module/Control/action
	'PATHINFO_QUERY_NAME'		=>	'i',
	// 是否过滤非DOMAIN配置项域名的访问，如果DOMAIN不配置或为空则本项无效
	'FILTER_DOMAIN'				=>	false,

	/**
	 * 模版引擎相关
	 */
	// 是否开启模版引擎
	'TEMPLATE_ENGINE_ON'		=>	true,
	// 是否开启模版缓存
	'TEMPLATE_CACHE_ON'			=>	true,
	// 模版缓存有效期
	'TEMPLATE_CACHE_EXPIRE'		=>	0,
	// 模版引擎名称
	'TEMPLATE_ENGINE'			=>	'YurunTPEView',
	// 模版标签左
	'TEMPLATE_TAG_LEFT'			=>	'<',
	// 模版标签右
	'TEMPLATE_TAG_RIGHT'		=>	'>',
	// 输出标签左
	'TEMPLATE_ECHO_VAR_TAG_LEFT'		=>	'<%=',
	// 输出标签右
	'TEMPLATE_ECHO_VAR_TAG_RIGHT'		=>	'%>',
	// 常量输出标签左
	'TEMPLATE_ECHO_CONST_TAG_LEFT'		=>	'<CONST:',
	// 常量输出标签右
	'TEMPLATE_ECHO_CONST_TAG_RIGHT'		=>	'>',
	// 配置输出标签左
	'TEMPLATE_ECHO_CONFIG_TAG_LEFT'		=>	'<CONFIG:',
	// 配置输出标签右
	'TEMPLATE_ECHO_CONFIG_TAG_RIGHT'	=>	'>',
	// 语言输出标签左
	'TEMPLATE_ECHO_LANG_TAG_LEFT'		=>	'<LANG:',
	// 语言输出标签右
	'TEMPLATE_ECHO_LANG_TAG_RIGHT'		=>	'>',
	// 是否优化php代码，合并php标签和语句
	'TEMPLATE_OPTIMIZE_PHP'				=>	true,

	/**
	 * 杂项
	 */
	// 时区设置
	'TIMEZONE' => 'Asia/Shanghai',
	// // 错误异常跳转页，非调试状态下使用
	'ERROR_URL'	=>	'',
	// 控制器returnData方法默认返回数据格式类型
	'CONTROL_RETURN_TYPE'		=>	'json',
	// 静态文件路径
	'PATH_STATIC' => 'Static',
);
