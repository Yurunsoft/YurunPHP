<?php
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
		$dataset = &$this->dataset;
		if(isset($dataset[0]) && !is_array($dataset[0]))
		{
			$s = count($dataset);
			for($i = 0;$i < $s;++$i)
			{
				$val = $dataset[$i];
				$dataset[$i] = array();
				if('value' === $this->dataset_1d_array_text_field)
				{
					$dataset[$i][$this->text_field] = $val;
				}
				else
				{
					$dataset[$i][$this->text_field] = $i;
				}
				if('value' === $this->dataset_1d_array_value_field)
				{
					$dataset[$i][$this->value_field] = $val;
				}
				else
				{
					$dataset[$i][$this->value_field] = $i;
				}
			}
		}
		parent::prepareView();
	}
}