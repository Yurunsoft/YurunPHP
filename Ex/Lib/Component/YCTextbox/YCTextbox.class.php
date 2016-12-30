<?php
class YCTextbox extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text'			=> '',
			'muiltline'		=> false,
			'text_field'	=> '',
	);
	/**
	 * 构造方法
	 * @param unknown $attrs
	 * @param string $tagName
	 */
	public function __construct($attrs = array(), $tagName = null)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
			'text','muiltline','text_field'
		));
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		if(null!==$this->text_field && null!==$this->dataset)
		{
			$this->getTextFromDataset();
		}
		$this->value = $this->text;
		$this->popup_type = $this->popup_type;
		if($this->muiltline)
		{
			$text = $this->data['text'];
			unset($this->data['text']);
			parent::prepareView();
			$this->data['text'] = $text;
		}
		else
		{
			parent::prepareView();
		}
	}
	private function getTextFromDataset()
	{
		if(!empty($this->text_field) && is_array($this->dataset) && count($this->dataset)>0)
		{
			if(isset($this->dataset[$this->text_field]))
			{
				$this->text = $this->dataset[$this->text_field];
			}
			else if(isset($this->dataset[0][$this->text_field]))
			{
				$this->text = $this->dataset[0][$this->text_field];
			}
		}
	}
}