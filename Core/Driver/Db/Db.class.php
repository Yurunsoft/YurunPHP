<?php
/**
 * 数据库驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
class Db extends Driver
{
	const RETURN_ISOK = 0;
	const RETURN_ROWS = 1;
	const RETURN_INSERT_ID = 2;
}
Db::init();