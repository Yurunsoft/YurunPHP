<?php
return array (
	'DB' => array (
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
	'APP_CACHE'	=>	array(
		'mem'	=>	array(
			'type'		=>	'Memcache',
			'option'	=>	array(
				'host'	=>	'127.0.0.1',
				'port'	=>	11211
			)
		),
		'memd'	=>	array(
			'type'		=>	'Memcache',
			'servers'	=>	array(
				array('127.0.0.1', 11211),
			)
		)
	)
);