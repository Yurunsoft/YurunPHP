<?php
return array (
	// 数据库配置
	'APP_DB' => array (
		/*'default'	=>	array(
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
		)*/
		'default'	=>	array(
			'type' => 'PDOMysql',
			'option'	=>	array(
				'host' => '127.0.0.1',
				'port' => '3306',
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'db_wxzl_video',
				'prefix' => 'tb_',
				'charset' => 'utf8',
			)
		)
	),
	// 默认使用default数据库配置
	'DEFAULT_DB'	=>	'default',

	'APP_CACHE'	=>	array(
		/*'memcache'	=>	array(
			'type'		=>	'Memcache',
			'option'	=>	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	11211,
				'timeout'	=>	1,
				'pconnect'	=>	false,
			)
		),*/
		/*'memcached'	=>	array(
			'type'		=>	'Memcached',
			'option'	=>	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	11211,
				'timeout'	=>	1,
				'pconnect'	=>	false,
			)
		),*/
		/*'redis'	=>	array(
			'type'		=>	'Redis',
			'option'	=>	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	6379,
				'timeout'	=>	1,
				'pconnect'	=>	false,
			)
		),*/
		/*'db'	=>	array(
			'type'		=>	'Db',
			'option'	=>	array(
				// 数据库配置别名
				'db_alias'		=>	'default',
				// 表全名，包括前缀，如果设置将无视table的值
				// 'table_name'	=>	'tb_cache',
				// 不包括表前缀的表名
				'table'			=>	'cache',
			)
		),*/
	),

	// 日志驱动配置
	'APP_LOG'	=>	array(
		// 数据库日志配置
		/*'db'	=>	array(
			'type'		=>	'Db',
			'option'	=>	array(
				'db_alias'		=>	'default',
				// 表全名，包括前缀，如果设置将无视table的值
				// 'table_name'	=>	'tb_config',
				// 不包括表前缀的表名
				'table'			=>	'config',
			),
		)*/
	),
	// 默认使用数据库日志
	// 'DEFAULT_LOG'	=>	'db',
	
	// 使用数据库Session
	/*'SESSION_SAVE_HANDLER'	=>	'user',
	'SESSION_USER_SAVE_HANDLER'	=>	'DBSession',
	'SESSION_DB_ALIAS'	=>	'default'*/
);