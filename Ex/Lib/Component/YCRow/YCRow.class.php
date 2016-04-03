<?php
class YCRow extends YCBase
{
	protected $renderMode = self::RENDER_MODE_NONE;
	private $table;
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		parent::prepareView();
		$this->table = &HtmlTable::getTable();
	}
	/**
	 * 渲染控件
	 */
	protected function __render()
	{
		if(!$this->table->isHeadStart)
		{
			echo '<thead>';
			$this->table->isHeadStart = true;
		}
		echo '<tr'.$this->attrsStr.'>';
	}
	/**
	 * 结束
	 */
	public function end()
	{
		echo '</tr>';
		parent::end();
	}
}