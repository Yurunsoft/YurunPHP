<?php
/**
 * 数据库驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
class Db extends Driver
{
	/**
	 * 返回操作是否执行成功
	 * @var int
	 */
	const RETURN_ISOK = 0;
	/**
	 * 返回语句影响行数
	 * @var int
	 */
	const RETURN_ROWS = 1;
	/**
	 * 返回最后插入的自增ID
	 * @var int
	 */
	const RETURN_INSERT_ID = 2;
}
Db::init();