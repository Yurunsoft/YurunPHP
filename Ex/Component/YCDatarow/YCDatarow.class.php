<?php
/**
 * 表格数据行
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCDatarow extends YCBase
{
	protected $renderMode = self::RENDER_MODE_NONE;
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		parent::prepareView();
	}
	/**
	 * 渲染控件
	 */
	protected function __render()
	{
		$table = &YCTable::getTable();
		if(!$table->isHeadEnd)
		{
			echo '</thead>';
			$table->isHeadEnd = true;
		}
		if(!$table->isBodyStart)
		{
			echo '<tbody>';
			$table->isBodyStart = true;
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
	public static function __getTemplatePHP($php)
	{
		$dataName = '$__'.str_replace('.','',uniqid('',true));
		$controlName = '$__table'.str_replace('.','',uniqid('',true));
		$php = preg_replace('/{([^}]+)}/', $dataName . '[\'\1\']', $php);
		return <<<PHP
<?php
	{$controlName} = YCBase::__getControl();
	{$dataName} = {$controlName}->_firstDataset();
	while(false!=={$dataName}){
?>
{$php}
<?php
	{$dataName} = {$controlName}->_nextDataset();
	}
?>
PHP
;
	}
}