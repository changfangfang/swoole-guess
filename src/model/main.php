<?php

class Main{
	
	static $swoole;
	static $worker_id;
	static $readPackage;
	static $cfg = array();//配置数据
	static $class = array();//类
	
	/**
	 * swoole onWorkerStart逻辑
	 */
	public static function onWorkerStart($serv, $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		
		self::$swoole = $serv;
		self::$worker_id = $worker_id;
		
		self::$class['data'] 		= new Data();
		self::$class['dataPool']	= new DataPool();
		self::$class['tick']		= new Tick();
		self::$class['tcp']			= new Tcp();
		self::$class['task']		= new Task();
		
		self::loadCfg();//加载cfg数据
		
		self::$class['tick']->init();//定时任务启动
		self::$class['data']->start();//数据加载
		self::logs('workerStart', 'sys');
	}
	
	/**
	 * swoole onWorkerStop逻辑
	 */
	public static function onWorkerStop(){
		self::$class['data']->stop();//数据加载
		self::logs('----onWorkerStop----');
	}
	
	/**
	 * register_shutdown_function 注册函数调用
	 */
	public static function processEnd(){
		self::$class['data']->stop();//数据加载
		self::logs('----processEnd----');
	}
	
	
	/**
	 * swoole onReceive事件逻辑
	 */
	public static function onReceive($fd, $packet_buff){
		$data = self::decodePack($packet_buff);
		Main::$class['tcp']->init($fd, $data);
	}
	
	
	/**
	 * swoole onClose事件逻辑
	 */
	public static function onClose($fd){
		//删除记录的ip
		global $uid_table,$fd_table;
		$fdInfo = $fd_table->get($fd);
		$fd_table->del($fd);
		if (null == $fdInfo || !isset($fdInfo['uid'])){
			return ;
		}
		
		$uid = $fdInfo['uid'];
		$uidInfo = $uid_table->get($uid);
		if (null == $uidInfo){
			return ;
		}
		
		self::task(self::getTableTaskId($uidInfo['tid']), 'connectClose', array('fd'=>$fd, 'uid'=>$uid, 'tid'=>$uidInfo['tid']));
	}
	
	
	/**
	 * swoole onTask事件逻辑
	 */
	public static function onTask($data){
		call_user_func_array(array(self::$class['task'], $data['method']), array($data['args']));
	}
	
	/**
	 * 指定taskid进程投递一个task任务
	 */
	public static function task($taskId, $method, $args){
		if (self::$swoole->taskworker){
			self::logs("Error: task error 1");
			return false;
		}
		$data = array('method'=>$method, 'args'=>$args);
		self::$swoole->task($data, $taskId);
	}
	
	
	
	/**
	 * swoole onPipeMessage事件逻辑
	 */
	public static function onPipeMessage($fromId, $message){
		$data = @json_decode($message, true);
		if (null == $data){
			self::logs("Error: on message error 1");
			return;
		}
		if (!isset($data['method'])){
			self::logs("Error: on message error 2");
			return;
		}
		if (!method_exists("Message", $data['method'])){
			self::logs("Error: on message error 3");
			return ;
		}
		call_user_func_array(array("Message", $data['method']), array($fromId, $data['args']));
	}
	
	
	public static function sendTcp($fd, $cmd, $data){
		$pack = self::encodePack($cmd, $data);
		if (false == $pack){
			self::logs("Error: send tcp error 1");
			return;
		}
		
		if (!self::$swoole->exist($fd)){
			return ;
		}
		self::$swoole->push($fd, $pack);
	}
	
	public static function sendTcpWithUid($uid, $cmd, $data){
		global $uid_table;
		$userInfo = $uid_table->get($uid);
		if (is_null($userInfo)){
			$cmdstr = sprintf("0x%'03X", $cmd);
			self::logs("Error: send tcp error 2, cmd:0x{$cmdstr}");
			return;
		}
		self::sendTcp($userInfo['fd'], $cmd, $data);
	}
	
	public static function broadcastTable($tid, $cmd, $data, $filters=array()){
		$table = self::$class['dataPool']->getTable($tid);
		if (is_null($table)){
			self::logs("Error: broadcast table error, tid:{$tid}");
			return ;
		}
		foreach ($table->playerList as $uid=>$userInfo){
			if (in_array($uid, $filters)){
				continue;
			}
			self::sendTcpWithUid($uid, $cmd, $data);
		}
		return ;
	}
	
	/**
	 * 加载cfg目录下配置
	 */
	public static function loadCfg($isStart=0){
		if(!$isStart && function_exists('opcache_reset')) {
			opcache_reset();
		}
		$cfgFile = CFG_ROOT.'service.php';
		if(file_exists($cfgFile)){
			$cfg = include $cfgFile;
			self::$cfg = array_merge(self::$cfg, (array)$cfg);
		}
	}
	
	/**
	 * 获取指定桌子所在进程的task id, 禁止改动
	 */
	public static function getTableTaskId($tid){
		$taskId = $tid % self::$swoole->setting['task_worker_num'];
		return $taskId;
	}
	
	
	public static function encodePack($cmd, $data){
		$packData = array('cmd'=>$cmd, 'data'=>$data);
		return json_encode($packData) . "@@";
	}
	
	public static function decodePack($pack){
		$pack = trim($pack, "@");
		return json_decode($pack, true);
	}
	
	/*
	 * 日志记录
	 */
	public static function logs($msg, $file='syslog', $size=1){
		if(is_array($msg)){
			$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
		}
		$msg = date('Y-m-d H:i:s').' '.self::$worker_id. ' '.$msg;
		echo "{$msg}\n";
	}
}