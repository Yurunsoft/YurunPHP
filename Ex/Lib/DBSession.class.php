<?php
class DBSession implements SessionHandlerInterface
{
	private $db;
	private $tableName;
	//打开session的时候会最开始执行这里。  
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

	public function close()
	{
		return true;
	}

	//从数据库中读取Session数据  
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

	//用户访问的时候存入新的session,或更新旧的session.  
	//同时读取session中的userid或adminid写入数据表。  
	public function write($id, $data)
	{
		$tableName = $this->db->parseField($this->tableName);
		$sessionIDField = $this->db->parseField('session_id');
		$dataField = $this->db->parseField('data');
		$expireField = $this->db->parseField('expire');
		$result = $this->db->query(<<<SQL
select * from {$tableName} where {$sessionIDField} = %s
SQL
,array($id));
		$expire = time() + Session::cacheExpire();
		if(isset($result['session_id']))
		{
			return $this->db->execute(<<<SQL
update {$tableName} set {$dataField} = %s,{$expireField} = %s where {$sessionIDField} = %s
SQL
,array($data,$expire,$id));
		}
		else
		{
			return $this->db->execute(<<<SQL
insert into {$tableName}({$sessionIDField},{$dataField},{$expireField}) values(%s,%s,%s)
SQL
,array($id,$data,$expire));
		}
	}

	//session销毁的时候，从数据表删除。  
	public function destroy($id)
	{
		$tableName = $this->db->parseField($this->tableName);
		$sessionIDField = $this->db->parseField('session_id');
		return $this->db->execute(<<<SQL
delete from {$tableName} where {$sessionIDField} = %s
SQL
,array($id));
	}

	//回收session的时候，让用户下线。记录下线时间。清除超期session。不是每次都会执行。是一个概率问题。可以在php.ini中调节。默认1/100。概率是session.gc_probability/session.gc_divisor。默认情况下，session.gc_probability ＝ 1，session.gc_divisor ＝100，可在php.ini中修改  
	public function gc($maxlifetime)
	{
		$tableName = $this->db->parseField($this->tableName);
		$time = time();
		$expireField = $this->db->parseField('expire');
		return $this->db->execute(<<<SQL
delete from {$tableName} where {$expireField} < {$time}
SQL
);
	}
}
