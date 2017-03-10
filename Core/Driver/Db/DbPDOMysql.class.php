<?php
class DbPDOMysql extends DbPDOBase
{
	/**
	 * 参数标识
	 * @var array
	 */
	public $param_flag = array ('`','`');

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
}