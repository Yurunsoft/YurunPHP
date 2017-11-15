<?php
/**
 * 单选/选择框组控件基类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class YCCheckRadioGroupBase extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text'							=> '',
			'text_align'					=> 'right',
			'text_field'					=> 'text',
			'value_field'					=> 'value',
			'checked_field'					=> 'checked',
			'theme'							=> 'default',
			'dataset_1d_array_text_field'	=> 'value',
			'dataset_1d_array_value_field'	=> 'key'
	);
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		if(isset($this->dataset[0]) && !is_array($this->dataset[0]))
		{
			$s = count($this->dataset);
			for($i = 0;$i < $s;++$i)
			{
				$val = $this->dataset[$i];
				$this->dataset[$i] = array();
				if('value' === $this->dataset_1d_array_text_field)
				{
					$this->dataset[$i][$this->text_field] = $val;
				}
				else
				{
					$this->dataset[$i][$this->text_field] = $i;
				}
				if('value' === $this->dataset_1d_array_value_field)
				{
					$this->dataset[$i][$this->value_field] = $val;
				}
				else
				{
					$this->dataset[$i][$this->value_field] = $i;
				}
			}
		}
		parent::prepareView();
	}
}