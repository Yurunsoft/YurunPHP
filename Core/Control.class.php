<?php
/**
 * 控制器类
 * @author Yurun <admin@yurunsoft.com>
 */
class Control
{
	protected $db;
	protected $view;
	function __construct()
	{
		$this->view = new View(Config::get('@.THEME_ON')?Config::get('@.THEME'):null,$this);
	}
}