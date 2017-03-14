<?php
class DbPDOMysql extends DbPDOBase
{
	/**
	 * 参数标识
	 * @var array
	 */
	public $paramFlag = array ('`','`');

	/**
	 * 构建DNS字符串
	 * @param array $option 
	 * @return string 
	 */
	public function buildDSN($option = null)
	{
		if(null === $option)
		{
			$option = $this->option;
		}
		$charset = (isset($option['charset']) ? $option['charset'] : 'utf8');
		// php<5.3.6的版本不认charset的处理
		if(-1 === version_compare(PHP_VERSION, '5.3.6'))
		{
			$this->option['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $charset;
		}
		return 'mysql:'
				 . 'host=' . (isset($option['host']) ? $option['host'] : '127.0.0.1')
				 . ';port=' . (isset($option['port']) ? $option['port'] : '3306')
				 . ';dbname=' . (isset($option['dbname']) ? $option['dbname'] : '')
				 . ';unix_socket=' . (isset($option['unix_socket']) ? $option['unix_socket'] : '')
				 . ';charset=' . $charset
				 ;
	}
	
	/**
	 * 获取结果行数
	 * @return int
	 */
	public function foundRows()
	{
		return (int)$this->handler->query('select found_rows()')->fetchColumn();
	}

	/**
	 * 获取数据库中所有数据表名
	 * @param string $dbname
	 * @return array
	 */
	public function getTables($dbName = null)
	{

	}

	/**
	 * 获取数据表中所有字段详细信息
	 * @param string $table
	 * @return array
	 */
	public function getFields($table)
	{

	}

	/**
	 * 构建SELECT语句
	 * @return string 
	 */
	public function buildSelectSQL()
	{
		$where = $this->parseCondition(isset($this->operationOption['where']) ? $this->operationOption['where'] : '');
		if ('' !== $where)
		{
			$where = 'where ' . $where . ' ';
		}
		return 'select ' . $this->parseDistinct()
				. $this->parseField(null,'*')
				. 'from '
				. $this->parseTable() . ' '
				. $this->parseJoin()
				. $where
				. $this->parseGroup()
				. $this->parseHaving()
				. $this->parseOrder()
				. $this->parseLimit()
				;
	}

	/**
	 * 构建INSERT语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	public function buildInsertSQL($table = null, $data = array())
	{
		$data = $this->params($data);
		if(isAssocArray($data))
		{
			$keys = array_keys($data);
			return 'insert into ' . $this->parseKeyword($this->table($table)) . '(' . implode(',',array_map(array($this,'parseKeyword'),$keys)) . ') values(:' . implode(',:',$keys) . ')';
		}
		else
		{
			return 'insert into ' . $this->parseKeyword($this->table($table)) . ' values(' . substr(str_repeat('?,',count($data)),0,-1) . ')';
		}
	}

	/**
	 * 构建UPDATE语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	public function buildUpdateSQL($table = null, $data = array())
	{
		// $data = $this->params($data);
		// $sql = 'update ' . $this->parseKeyword($this->table($table)) . ' set ';
		// foreach($data as $key => $value)
		// {
		// 	$sql .= $this->parseKeyword($key) . '=?,';
		// }
		// $sql = substr($sql,0,-1);

	}

	/**
	 * parseOrderByField
	 * @param mixed $order 
	 * @return string 
	 */
	public function parseOrderByField($order)
	{
		if(isset($order[0],$order[1]))
		{
			if(!is_array($order[1]))
			{
				$order[1] = explode(',', $order[1]);
			}
			$list = array();
			foreach($order[1] as $item)
			{
				$paramName = $this->getParamName();
				$this->operationOption['params'][$paramName] = $item;
				$list[] = ':' . $paramName;
			}
			return 'field(' . $this->parseKeyword($order[0]) . ',' . implode(',', $list) . ')';
		}
		else
		{
			return 1;
		}
	}
}