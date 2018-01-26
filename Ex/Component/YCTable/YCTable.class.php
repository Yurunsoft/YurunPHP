<?php
/**
 * 表格控件
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCTable extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'auto_head'	=> true,	// 是否根据查询结果自动生成表格头
	);
	public $isHeadStart = false;
	public $isHeadEnd = false;
	public $isBodyStart = false;
	public $isBodyEnd = false;
	public $datasetIndex = 0;
	private static $stack = array();		// 堆栈
	private static $stackSize = 0;
	private $_cols = array();		// 列们
	protected $printEnd = true;
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
			'auto_head'
		));
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		self::$stack[] = $this;
		++self::$stackSize;
		parent::prepareView();
	}
	public function end()
	{
		array_pop(self::$stack);
		--self::$stackSize;
		$this->view->isHeadStart = $this->isHeadStart;
		$this->view->isHeadEnd = $this->isHeadEnd;
		$this->view->isBodyStart = $this->isBodyStart;
		$this->view->isBodyEnd = $this->isBodyEnd;
		if(empty($this->_cols))
		{
			foreach($this->dataset[0] as $key=>$column)
			{
				if(!is_int($key))
				{
					$col = new stdClass;
					$col->field = $key;
					$this->_cols[] = $col;
				}
			}
		}
		$this->view->cols = $this->_cols;
		parent::render('end');
		parent::end();
	}
	public function addColumn($col)
	{
		$this->_cols[] = $col;
	}
	public static function &getTable()
	{
		return self::$stack[self::$stackSize-1];
	}
	public function _firstDataset()
	{
		$this->datasetIndex = 0;
		return reset($this->dataset);
	}
	public function _nextDataset()
	{
		++$this->datasetIndex;
		return next($this->dataset);
	}
}