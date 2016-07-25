<?php
class IndexControl extends Control
{
	public function index()
	{
		$this->view->set('msg',Model::obj('test')->getMsg());
		$this->view->display();
	}
}