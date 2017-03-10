<?php
/**
 * 数据库层接口
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
interface IDb
{
	/**
	 * 构造DSN字符串
	 * @param array $option 
	 * @return string 
	 */
	public function buildDSN($option = null);

	/**
	 * 获取结果行数
	 * @return int
	 */
	public function foundRows();

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
}