<?php
/**
 * 表格列
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCCol extends YCBase
{
	private $_table;
	protected $renderMode = self::RENDER_MODE_NONE;
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
			'type'
		));
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		parent::prepareView();
		$this->_table = YCTable::getTable();
		$this->_table->addColumn($this);
	}
	/**
	 * 渲染控件
	 */
	protected function __render()
	{
		if(!$this->_table->isHeadStart)
		{
			echo '<thead>';
			$this->_table->isHeadStart = true;
		}
		echo '<th'.$this->attrsStr.'>';
	}
	public function end()
	{
		echo '</th>';
		parent::end();
	}
}