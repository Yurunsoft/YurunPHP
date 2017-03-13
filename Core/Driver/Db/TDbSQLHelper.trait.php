<?php
trait TDbSQLHelper
{
	/**
	 * 运算符
	 * @var array
	 */
	public $operators = array ();

	/**
	 * 逻辑运算符
	 * @var array
	 */
	public $logicOperators = array ('and','or','xor','and not','or not','xor not');

	/**
	 * 参数标识
	 * @var array
	 */
	public $paramFlag = array ('','');

	/**
	 * 是否启用参数标识
	 * @var bool
	 */
	public $isUseParamFlag = true;

	/**
	 * 处理关键词
	 * @param string $keyword 
	 * @return string
	 */
	public function parseKeyword($keyword)
	{
		return $this->paramFlag[0] . $keyword . $this->paramFlag[1];
	}
}