<?php
/**
 * 数据库日志驱动
	表结构：
	CREATE TABLE `tb_log` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
	`content` varchar(255) NOT NULL,
	`time` timestamp NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class LogDb extends LogBase
{
	/**
	 * 日志表名
	 */
	public $tableName = '';
	/**
	 * 数据库操作对象
	 */
	public $db;
	/**
	 * 构造方法
	 * @param array $option        	
	 */
	public function __construct($option = null)
	{
		parent::__construct($option);
		// 根据数据库配置别名连接数据库
		$dbAlias = isset($option['db_alias']) ? $option['db_alias'] : null;
		if(null !== $dbAlias)
		{
			$this->db = Db::getInstance($dbAlias);
			// 根据配置获取表名
			if(isset($option['table_name']))
			{
				$this->tableName = $option['table_name'];
			}
			else if(isset($option['table']))
			{
				$this->tableName = $this->db->tablePrefix . $option['table'];
			}
			else
			{
				$this->tableName = $this->db->tablePrefix . 'log';
			}
		}
	}
	/**
	 * 添加一条日志，支持自定义字段
	 * @param string $content
	 * @param array $option
	 */
	public function add($content, $option = array())
	{
		// 优先使用$option中的数据
		if(!isset($option['content']))
		{
			$option['content'] = $content;
		}
		if(!isset($option['time']))
		{
			$option['time'] = date('Y-m-d H:i:s');
		}
		$this->data[] = $option;
	}
	
	/**
	 * 保存日志
	 * @return bool
	 */
	public function save()
	{
		$this->db->begin();
		foreach($this->data as $item)
		{
			$this->db->insert($this->tableName,$item);
		}
		$this->db->commit();
		return true;
	}
}