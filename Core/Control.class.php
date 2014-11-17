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

	public function __call($name,$arguments)
	{
		// 实现动作名为php保留字符
		$name="_R_{$name}";
		if(method_exists($this,$name))
		{
			$this->$name();
		}
	}
}