<?php
/**
 * YurunPHP分页类
 * @copyright 2008-2014 Yurunsoft.com All rights reserved.
 * @author 宇润 (Yurun) <admin@yurunsoft.com>
 */
class Page
{
	// 记录总数
	private $totalRecords;
	// 每页显示数量
	private $pageShow;
	// 总页数
	private $totalPages;
	// 当前页
	private $currPage;
	// url规则
	private $rule;
	// url参数，不包括页码
	private $param;
	// 页码url参数名
	private $pageField;
	// 显示多少个页码
	private $pageItemCount;
	// 模版名
	private $template;
	// 视图
	private $view;
	/**
	 * 构造方法
	 * @param int $totalRecords
	 * @param int $pageShow
	 * @param int $currPage
	 * @param string $view
	 * @param string $rule
	 * @param string $param
	 */
	public function __construct($totalRecords,$pageShow,$currPage,$view=null,$rule=null,$param=null)
	{
		$this->setTotalRecords($totalRecords);
		$this->setPageShow($pageShow);
		$this->setCurrPage($currPage);
		$this->setRule($rule);
		$this->setParam($param);
		$this->setPageField(Config::get('@.PAGEBAR_FIELD','p'));
		$this->setPageItemCount(Config::get('@.PAGEBAR_ITEM_COUNT',10));
		$this->setTemplate(Config::get('@.PAGEBAR_TEMPLATE','@m/pagebar'));
		$this->view=$view;
	}

	/**
	 * 获取总页数
	 * @return int
	 */
	public function getTotalPages()
	{
		return $this->totalPages;
	}

	/**
	 * 设置记录数量
	 * @param int $totalRecords
	 */
	public function setTotalRecords($totalRecords)
	{
		$this->totalRecords=$totalRecords;
		$this->calcTotalPages();
	}

	/**
	 * 获取记录数量
	 * @return int
	 */
	public function getTotalRecords()
	{
		return $this->totalRecords;
	}

	/**
	 * 设置每页显示数量
	 * @param int $pageShow
	 */
	public function setPageShow($pageShow)
	{
		$this->pageShow=$pageShow;
		$this->calcTotalPages();
	}

	/**
	 * 获取每页显示数量
	 * @return int
	 */
	public function getPageShow()
	{
		return $this->pageShow;
	}

	/**
	 * 设置当前页码
	 * @param int $currPage
	 */
	public function setCurrPage($currPage)
	{
		$this->currPage=$currPage;
	}

	/**
	 * 获取当前页码
	 * @return int
	 */
	public function getcurrPage()
	{
		return $this->currPage;
	}

	/**
	 * 设置url规则
	 * @param string $rule
	 */
	public function setRule($rule)
	{
		if(null===$rule)
		{
			$rule='';
			if (Config::get('@.MODULE_ON'))
			{
				$rule=Dispatch::module().'/';
			}
			$rule.=Dispatch::control().'/'.Dispatch::action();
		}
		$this->rule=$rule;
	}

	/**
	 * 获取url规则
	 * @return string
	 */
	public function getRule()
	{
		return $this->rule;
	}

	/**
	 * 设置url参数
	 * @param array $param
	 */
	public function setParam($param)
	{
		if(null===$param)
		{
			$this->param=$_GET;
		}
		else 
		{
			$this->param=$param;
		}
		$this->parseParam();
	}

	/**
	 * 获取url参数
	 * @return string
	 */
	public function getParam()
	{
		return $this->param;
	}

	/**
	 * 设置页码GET参数名
	 * @param string $pageField
	 */
	public function setPageField($pageField)
	{
		$this->pageField=$pageField;
		$this->parseParam();
	}

	/**
	 * 获取页码GET参数名
	 * @return string
	 */
	public function getPageField()
	{
		return $this->pageField;
	}

	/**
	 * 设置页码显示数量
	 * @param int $pageItemCount
	 */
	public function setPageItemCount($pageItemCount)
	{
		$this->pageItemCount=(int)$pageItemCount;
	}

	/**
	 * 获取页码显示数量
	 * @return int
	 */
	public function getPageItemCount()
	{
		return $this->leftPages;
	}

	/**
	 * 设置模版
	 * @param string $template
	 */
	public function setTemplate($template)
	{
		$this->template=$template;
	}

	/**
	 * 获取模版
	 * @return string
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * 输出分页条
	 */
	public function display()
	{
		include $this->view->getTemplateFile($this->template);
	}

	/**
	 * 分页算法1
	 * 当前页1-10从1开始，11-20从11开始，以此类推
	 */
	private function pageItem_1()
	{
		$file=dirname($this->view->getTemplateFile($this->template)).'/pagebar_item'.Config::get('@.TEMPLATE_EXT');
		// 确认开始的数字
		$start=(int)(($this->currPage-1)/$this->pageItemCount)*$this->pageItemCount+1;
		// 显示几个页码
		$itemCount=$this->pageItemCount-(int)($start/10);
		// 确认结束的数字
		$end=$start+$this->pageItemCount-1;
		if($end>$this->totalPages)
		{
			$end=$this->totalPages;
		}
		for($page=$start;$page<=$end;++$page)
		{
			include $file;
		}
	}

	/**
	 * 分页算法2
	 * 当前页显示在分页条中间
	 */
	private function pageItem_2()
	{
		$file=dirname($this->view->getTemplateFile($this->template)).'/pagebar_item'.Config::get('@.TEMPLATE_EXT');
		// 确认开始的数字
		$start=$this->currPage-$this->pageItemCount/2;
		if($start<=0)
		{
			$start=1;
		}
		// 显示几个页码
		$itemCount=$this->pageItemCount-(int)($start/10);
		// 确认结束的数字
		$end=$start+$this->pageItemCount-1;
		if($end>$this->totalPages)
		{
			$end=$this->totalPages;
		}
		for($page=$start;$page<=$end;++$page)
		{
			include $file;
		}
	}

	/**
	 * 输出带页码的URL
	 * @param string $page
	 */
	private function url($page)
	{
		$param=$this->param;
		$param[$this->pageField]=$page;
		echo Dispatch::url($this->rule,$param);
	}

	/**
	 * 计算总页数
	 */
	private function calcTotalPages()
	{
		if($this->pageShow>0 && is_numeric($this->totalRecords))
		{
			$this->totalPages=(int)($this->totalRecords/$this->pageShow);
			if($this->totalRecords % $this->pageShow!=0)
			{
				++$this->totalPages;
			}
			else if($this->totalPages<1)
			{
				$this->totalPages=1;
			}
		}
	}

	/**
	 * 处理参数
	 */
	private function parseParam()
	{
		if(isset($this->param[$this->pageField]))
		{
			unset($this->param[$this->pageField]);
		}
	}
}