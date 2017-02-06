<?php
/**
 * 表格数据列
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCDatacol extends YCBase
{
	protected $renderMode = self::RENDER_MODE_NONE;
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
			'title','field','rowData','filter','datasetIndex'
		));
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		$_table = &YCTable::getTable();
		$this->rowData = current($_table->dataset);
		$this->datasetIndex = $_table->datasetIndex + 1;
		parent::prepareView();
	}

	protected function __render()
	{
		echo '<td'.$this->attrsStr.'>';
		if(''===preg_replace('/[\s\n\r\t　]/','',$this->innerHtml))
		{
			if('index'===$this->type)
			{
				echo $this->datasetIndex;
			}
			else if(empty($this->filter)) 
			{
				echo $this->rowData[$this->field];
			}
			else
			{
				echo execFilter($this->rowData[$this->field],$this->filter);
			}
		}
	}
	public function end()
	{
		echo '</td>';
		parent::end();
	}
}