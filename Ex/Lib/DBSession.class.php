<?php
/**
 * 数据库Session处理类
	表结构：
	CREATE TABLE `tb_session` (
	`session_id` varchar(255) NOT NULL COMMENT 'SessionID',
	`data` text COMMENT '数据',
	`update_time` int(11) NOT NULL COMMENT '过期',
	PRIMARY KEY (`session_id`),
	KEY `update_time` (`update_time`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class DBSession implements SessionHandlerInterface
{
	/**
	 * 数据库操作对象
	 */
	private $db;
	/**
	 * 表名
	 */
	private $tableName;
	/**
	 * 打开Session
	 * @param string $savePath
	 * @param string $sessionName
	 * @return bool
	 */
	public function open($savePath, $sessionName)
	{
		$this->db = Db::getInstance(Config::get('@.SESSION_DB_ALIAS'));
		if(null !== $this->db)
		{
			$tableName = Config::get('@.SESSION_DB_TABLENAME','');
			if('' !== $tableName)
			{
				$this->tableName = $tableName;
			}
			else
			{
				$tableName = Config::get('@.SESSION_DB_TABLE','');
				if('' !== $tableName)
				{
					$this->tableName = $this->db->tablePrefix . $tableName;
				}
				else
				{
					$this->tableName = $this->db->tablePrefix . 'session';
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * 关闭Session
	 * @return bool
	 */
	public function close()
	{
		return true;
	}

	/**
	 * 读Session
	 * @param string $id Session ID
	 * @return mixed
	 */
	public function read($id)
	{
		$tableName = $this->db->parseField($this->tableName);
		$sessionID = $this->db->parseField('session_id');
		$result = $this->db->query(<<<SQL
select * from {$tableName} where {$sessionID} = %s
SQL
,array($id));
		if(isset($result['data']))
		{
			return $result['data'];
		}
		else
		{
			return '';
		}
	}

	/**
	 * 写Session
	 * @param string $id Session ID
	 * @param string $data 数据
	 * @return bool
	 */
	public function write($id, $data)
	{
		$tableName = $this->db->parseField($this->tableName);
		$sessionIDField = $this->db->parseField('session_id');
		$dataField = $this->db->parseField('data');
		$updateTimeField = $this->db->parseField('update_time');
		$result = $this->db->query(<<<SQL
select * from {$tableName} where {$sessionIDField} = %s
SQL
,array($id));
		$updateTime = time();
		if(isset($result['session_id']))
		{
			return $this->db->execute(<<<SQL
update {$tableName} set {$dataField} = %s,{$updateTimeField} = %s where {$sessionIDField} = %s
SQL
,array($data,$updateTime,$id));
		}
		else
		{
			return $this->db->execute(<<<SQL
insert into {$tableName}({$sessionIDField},{$dataField},{$updateTimeField}) values(%s,%s,%s)
SQL
,array($id,$data,$updateTime));
		}
	}

	/**
	 * 销毁Session
	 * @param string $id Session ID
	 * @return bool
	 */
	public function destroy($id)
	{
		$tableName = $this->db->parseField($this->tableName);
		$sessionIDField = $this->db->parseField('session_id');
		return $this->db->execute(<<<SQL
delete from {$tableName} where {$sessionIDField} = %s
SQL
,array($id));
	}

	/**
	 * 垃圾回收
	 * @param string $maxlifetime Session有效时间
	 * @return bool
	 */
	public function gc($maxlifetime)
	{
		$tableName = $this->db->parseField($this->tableName);
		$updateTimeField = $this->db->parseField('updateTime');
		$time = time() - $maxlifetime;
		return $this->db->execute(<<<SQL
delete from {$tableName} where {$updateTimeField} < {$time}
SQL
);
	}
}
