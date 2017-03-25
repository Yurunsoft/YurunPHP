<?php
class IndexControl extends Control
{
	public function index()
	{
		// 驱动使用
		// Cache::set('memcache.yurunphp',Yurun::YURUN_VERSION);
		// echo Cache::get('memcache.yurunphp');
		$db = Db::getInstance();
		// var_dump($db->query('select * from wxzl_channel where ID = ?',array(2)),$db->getError());
		// var_dump($db->insert('wxzl_dict',array('Name'=>'1')),$db->getError());
		// var_dump($db->insert('wxzl_dict',array('Name'=>'2'),Db::RETURN_INSERT_ID),$db->getError());
		// var_dump($db->insert('wxzl_dict',array('Name'=>'3'),Db::RETURN_ROWS),$db->getError());
		$db->orderByField(1);

		return;
		$model = new TestModel;
		$this->view->msg = $model->getMsg();
		$this->view->memory = memory_get_usage() / 1024;
		// $this->view->xxx和$this->view->set('xxx','')是等价的
		$this->view->set('time', (microtime(true) - YURUN_START) * 1000);
		$this->view->display();
	}
}