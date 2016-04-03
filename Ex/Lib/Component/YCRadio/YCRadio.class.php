<?php
class YCRadio extends HtmlCheckRadioBase
{
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
		$this->attrsDefault['text'] = '单选框';
	}
}