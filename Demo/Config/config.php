<?php
return array (
	'APP_DB' => array (
		'default'	=>	array(
			'type' => 'Mysqli',
			'option'	=>	array(
				'host' => '127.0.0.1',
				'port' => '3306',
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'db_weibo001',
				'prefix' => 'tb_',
				'charset' => 'utf8',
			)
		)
	),
	'DEFAULT_DB'	=>	'default',
	// 日志驱动配置
	'APP_CONFIG'	=>	array(
		'db'	=>	array(
			'type'		=>	'Db',
			'option'	=>	array(
				'db_alias'		=>	'default',
			),
		)
	),
	
	// 自定义Session存储
	'SESSION_SAVE_HANDLER'	=>	'user',
	'SESSION_USER_SAVE_HANDLER'	=>	'DBSession',
	'SESSION_DB_ALIAS'	=>	'default'
);