<?php
/**
 * 数据库层接口
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
interface IDb
{
	/**
	 * 连接数据库
	 * @param array $option 
	 */
	public function connect($option = array());

	/**
	 * 构建DNS字符串
	 * @param array $option 
	 * @return string 
	 */
	public function buildDSN($option = null);

	/**
	 * 获取是否已连接
	 * @return boolean
	 */
	public function isConnect();

	/**
	 * 获取错误信息
	 * @return string 
	 */
	public function getError();

	/**
	 * 获取错误代码
	 * @return mixed 
	 */
	public function getErrorCode();

	/**
	 * 准备一个查询
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function prepareQuery($sql,$params = array());

	/**
	 * 执行一个SQL语句，返回影响的行数
	 * @param string $sql 
	 * @param array $params 
	 * @return int 
	 */
	public function execute($sql,$params = array());

	/**
	 * 查询多条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function query($sql,$params = array());

	/**
	 * 查询一列数据
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function queryColumn($sql,$params = array());

	/**
	 * 查询一条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getOne($sql,$params = array());

	/**
	 * 执行SQL语句，返回第一行第一列
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getScalar($sql,$params = array());

	/**
	 * 返回最后插入行的ID或序列值
	 * @param string $name 应该返回ID的那个序列对象的名称。比如，PDO_PGSQL() 要求为 name 参数指定序列对象的名称。
	 * @return string
	 */
	public function lastInsertID($name = null);

	/**
	 * 获取结果行数
	 * @return int
	 */
	public function foundRows();

	/**
	 * 获取影响行数
	 * @return int
	 */
	public function rowCount();

	/**
	 * 开启事务
	 * @return bool 
	 */
	public function begin();

	/**
	 * 提交事务
	 * @return bool 
	 */
	public function commit();

	/**
	 * 回滚事务
	 * @return bool 
	 */
	public function rollback();

	/**
	 * 检查是否在一个事务内
	 * @return bool 
	 */
	public function inTransaction();

	/**
	 * 获取数据库中所有数据表名
	 * @param string $dbname
	 * @return array
	 */
	public function getTables($dbName = null);

	/**
	 * 获取数据表中所有字段详细信息
	 * @param string $table
	 * @return array
	 */
	public function getFields($table);

	/**
	 * 锁定表
	 * @param array $option 
	 * @return bool 
	 */
	public function lockTable($option = null);

	/**
	 * 解锁表
	 * @param array $option 
	 * @return bool 
	 */
	public function unlockTable($option = null);
}