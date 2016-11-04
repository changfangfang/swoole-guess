<?php
/**
 * 进程数据管理类
 */
class Data{
	
	/**
	 * 从存储中加载已经注册的进程数据
	 * 注意： load方法必须在register之后调用
	 */
	public function start(){
		DataLoader::registerObject(Main::$class['tick']->mainTimer);
		DataLoader::registerObject(Main::$class['dataPool']);
		//从存储中加载进程数据
		DataLoader::load();
	}
	
	
	/**
	 * 进程stop的时候， 数据处理逻辑
	 */
	public function stop(){
		
		global $crontab_work_table;
		$woerId = Main::$swoole->worker_id;
		$crontab_work_table->incr($woerId, 'ver', 1);
		
		//保存已注册的进程数据
		DataLoader::save();
	}
	
}