<?php
return array(
	// 框架核心类
	'CORE_CLASSES' => array(
		'ArrayData',
		'Control',
		'Cookie',
		'Dispatch',
		'Driver',
		'Event',
		'Lang',
		'Model',
		'Request',
		'Response',
		'Session',
		'Task',
		'Validator',
		'View',
		'DbOperation',
	),
	// 框架驱动
	'CORE_DRIVER_CLASSES' => array(
		'Config',
		'Cache',
		'Db',
		'Log'
	),
	// 配置文件夹路径
	'CONFIG_PATH' => 'Config',
	// 核心配置驱动配置
	'CORE_CONFIG' => array(
		'Core' => array(
			'type' => 'PHP',
			'option' => array(
				'filename' => ROOT_PATH . 'Config/config.php',
			)
		)
	),
	'LOG_PATH' => 'Logs',
);
