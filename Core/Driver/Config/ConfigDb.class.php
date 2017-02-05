<?php
/**
 * 数据库配置驱动基类
	表结构：
	CREATE TABLE `tb_config` (
		`key` varchar(255) NOT NULL COMMENT '键名',
		`value` varchar(255) COMMENT '值',
		PRIMARY KEY (`key`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class ConfigDb extends ConfigBase
{
	/**
	 * 配置表名
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
				$this->tableName = $this->db->tablePrefix . 'config';
			}
			$this->loadData();
		}
	}
	/**
	 * 从数据库加载数据
	 */
	public function loadData()
	{
		$data = $this->db->queryA('select * from ' . $this->db->parseField($this->tableName));
		$this->data = array();
		foreach($data as $item)
		{
			$this->data[$item['key']] = $item['value'];
		}
	}
	/**
	 * 保存数据
	 * @param array $option 参数
	 */
	public function save($option = array())
	{
		$tableName = $this->db->parseField($this->tableName);
		$keyField = $this->db->parseField('key');
		$valueField = $this->db->parseField('value');
		foreach($this->data as $key => $value)
		{
			$dataExists = $this->db->queryValue("select 1 from {$tableName} where {$keyField} = %s",array($key));
			if($dataExists)
			{
				$this->db->update(array('value'=>$value),array('from'=>$this->tableName));
			}
			else
			{
				$this->db->insert($this->tableName,array('key'=>$key,'value'=>$value));
			}
		}
	}
}