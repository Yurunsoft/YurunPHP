<?php
/**
 * 数据库缓存驱动类
	表结构：
	CREATE TABLE `tb_cache` (
	  `key` varchar(255) NOT NULL COMMENT '键名',
	  `value` mediumblob COMMENT '值',
	  `expire` int(11) NOT NULL COMMENT '过期时间',
	  PRIMARY KEY (`key`),
	  KEY `expire` (`expire`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	如果您不是使用的MySQL数据库，请自行修改SQL语句或手动创建该结构的表
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class CacheDb extends CacheBase
{
	public $tableName = '';
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		$dbAlias = isset($option['db_alias']) ? $option['db_alias'] : null;
		if(null !== $dbAlias)
		{
			$this->cache = Db::getInstance($dbAlias);
			if(isset($option['table_name']))
			{
				$this->tableName = $option['table_name'];
			}
			else if(isset($option['table']))
			{
				$this->tableName = $this->cache->tablePrefix . $option['table'];
			}
			else
			{
				$this->tableName = $this->cache->tablePrefix . 'cache';
			}
		}
	}
	
	/**
	 * 清空缓存
	 * @return bool
	 */
	public function clear()
	{
		return $this->cache->execute('delete from ' . $this->cache->parseField($this->tableName));
	}

	/**
	 * 获取缓存内容
	 * @param string $alias 别名
	 * @param mixed $default 默认值或者回调
	 * @param array $config 配置
	 * @return mixed
	 */
	public function get($alias, $default = false, $config = array())
	{
		$tableName = $this->cache->parseField($this->tableName);
		$key = $this->cache->parseField('key');
		$expire = $this->cache->parseField('expire');
		$time = time();
		$result = $this->cache->query(<<<SQL
select * from {$tableName} where {$key} = %s and ({$expire} = 0 or {$expire} >= {$time})
SQL
,array($alias));
		if(isset($result['key']))
		{
			return $result['value'];
		}
		else
		{
			return $this->parseDefault($default);
		}
	}

	/**
	 * 删除缓存
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function remove($alias, $config = array())
	{
		$tableName = $this->cache->parseField($this->tableName);
		$key = $this->cache->parseField('key');
		return $this->cache->execute(<<<SQL
delete from {$tableName} where {$key} = %s
SQL
,array($alias));
	}

	/**
	 * 设置缓存
	 * @param string $alias 别名
	 * @param string $value 缓存内容
	 * @param array $config 配置
	 * @return bool
	 */
	public function set($alias, $value, $config = array())
	{
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		if($expire > 0)
		{
			$expire += time();
		}
		$tableName = $this->cache->parseField($this->tableName);
		$keyField = $this->cache->parseField('key');
		$valueField = $this->cache->parseField('value');
		$expireField = $this->cache->parseField('expire');
		$result = $this->cache->query(<<<SQL
select * from {$tableName} where {$keyField} = %s
SQL
,array($alias));
		if(isset($result['key']))
		{
			return $this->cache->execute(<<<SQL
update {$tableName} set {$valueField} = %s,{$expireField} = %i where {$keyField} = %s
SQL
,array($value,$expire,$alias));
		}
		else
		{
			return $this->cache->execute(<<<SQL
insert into {$tableName}({$keyField},{$valueField},{$expireField}) values(%s,%s,%i)
SQL
,array($alias,$value,$expire));
		}
	}

	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		$tableName = $this->cache->parseField($this->tableName);
		$key = $this->cache->parseField('key');
		return 0 < $this->cache->queryValue(<<<SQL
select 1 from {$tableName} where {$key} = %s
SQL
,array($alias));
	}
	/**
	 * 清除过期缓存
	 * @return type
	 */
	public function clearExpire()
	{
		$tableName = $this->cache->parseField($this->tableName);
		$expireField = $this->cache->parseField('expire');
		$time = time();
		return $this->cache->execute(<<<SQL
delete from {$tableName} where {$expireField} <> 0 and {$expireField} < {$time}
SQL
);
	}
}