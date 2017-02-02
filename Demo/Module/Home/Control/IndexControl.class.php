<?php
class IndexControl extends Control
{
	public function index()
	{
//		Cache::create('mem');
//		var_dump($memcache->set('a','test'));
		
//		var_dump($memcache->remove('a'),$memcache->remove('var_key'));
//		var_dump($memcache->get('a'));
		
//		var_dump(Cache::get('var_key', '', null, 'mem'));
//		
//		var_dump(getFirstWord('CacheAPC'),getFirstWord('ABCModel'));exit;
		
//		$a = new CacheAPCu;
//		$a->set('a', 'www.yurunsoft.com');
//		var_dump($a->get('a'));
//		$a->clear();
//		var_dump($a->get('a'));
		
		//连接本地的 Redis 服务
//		$redis = new Redis();
//		$redis->connect('127.0.0.1', 6379);
//		echo "Connection to server sucessfully";
//		//设置 redis 字符串数据
//		$redis->set("tutorial-name", array(1,2,3));
//		// 获取存储的数据并输出
//		var_dump($redis->get("tutorial-name"));		
		
		$redis = new CacheRedis();
		//设置 redis 字符串数据
		var_dump($redis->set("tutorial-name", 'abc'));
		// 获取存储的数据并输出
		var_dump($redis->get("tutorial-name"));	
		exit;
		echo 'hello world';
		echo '内存占用：' . memory_get_usage() . "b<br/>";
		echo microtime(true) - YURUN_START;
		return;
		var_dump(new Upload);
		$this->view->set('msg',Model::obj('test')->getMsg());
		$this->view->set('time', microtime(true) - YURUN_START);
		$this->view->display();
	}
}