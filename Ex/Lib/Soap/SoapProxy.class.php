<?php
class SoapProxy
{
	/**
	 * 代理的类名
	 */
	public $class;
	/**
	 * wsdl中的Style
	 */
	public $wsdlStyle;
	/**
	 * wsdl中的use
	 */
	public $wsdlUse;
	/**
	 * 代理的类实例
	 */
	public $instance;
	public function __construct($class,$wsdlStyle,$wsdlUse)
	{
		$this->class = $class;
		$this->wsdlStyle = $wsdlStyle;
		$this->wsdlUse = $wsdlUse;
		$this->instance = new $class;
	}
	public function __call($methodName, $args)
	{
		return array($methodName . 'Result' => call_user_func_array(array($this->instance,$methodName),(array)$args[0]));
	}
}