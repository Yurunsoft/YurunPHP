<?php
class YCPagebar extends YCBase
{
	protected static $_count = 0;
	public function __construct($attrs = array(), $tagName = null)
	{
		parent::__construct($attrs, $tagName);
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		$this->show_style = Config::get('@.pagebar_default_show_style',1);
		$this->page_field = Config::get('@.pagebar_default_field','p');
		$this->page_item_count = Config::get('@.pagebar_default_item_count',10);
		if(null===$this->get('page_show_field',null))
		{
			$this->page_show_field = Config::get('@.page_show_field','show');
			if(empty($this->page_show))
			{
				$this->page_show = Request::all($this->page_show_field);
			}
		}
		if(empty($this->page_show))
		{
			$this->page_show = Config::get('@.pagebar_default_page_show',10);
		}
		if(!isset($this->id))
		{
			$this->id = 'Pagebar'.(self::$_count+1);
		}
		parent::prepareView();
	}
	/**
	 * 渲染控件
	 */
	public function render()
	{
		if(!is_numeric($this->curr_page) || $this->curr_page<1)
		{
			$this->curr_page = Request::all($this->page_field,1);
		}
		else if($this->curr_page<0)
		{
			$this->curr_page = 1;
		}
		if(!is_numeric($this->total_records) || $this->total_records<0)
		{
			$this->total_records = 0;
		}
		$this->calcTotalPages();
		$this->parseParam();
		$this->view->set('total_records',$this->total_records);
		$this->view->set('page_show_field',$this->page_show_field);
		$this->view->set('page_show',$this->page_show);
		$this->view->set('total_pages',$this->total_pages);
		$this->view->set('curr_page',$this->curr_page);
		$this->view->set('page_field',$this->page_field);
		$this->view->set('page_item_count',$this->page_item_count);
		$this->view->set('url_rule',$this->url_rule);
		$this->view->set('url_param',$this->url_param);
		$this->view->set('formid',$this->formid);
		if(0===self::$_count)
		{
			parent::render('include');
		}
		parent::render('pagebar_' . $this->show_style);
		++self::$_count;
	}
	/**
	 * 计算总页数
	 */
	protected function calcTotalPages()
	{
		if(false === $this->total_pages)
		{
			if($this->page_show>0 && is_numeric($this->total_records))
			{
				$this->total_pages=(int)($this->total_records/$this->page_show);
				if($this->total_records % $this->page_show!=0)
				{
					++$this->total_pages;
				}
				else if($this->total_pages<1)
				{
					$this->total_pages=1;
				}
			}
			else
			{
				$this->total_pages=1;
			}
		}
	}
	
	/**
	 * 处理参数
	 */
	protected function parseParam()
	{
		if(isset($this->param[$this->page_field]))
		{
			unset($this->param[$this->page_field]);
		}
	}
}