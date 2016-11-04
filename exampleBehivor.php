<?php
//这个文件的代码在reload的时候全部会重新加载
include_once SERVER_ROOT . 'src/load.php';

class exampleBehivor extends SwooleBehavior{
	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff){
		try {
			Main::onReceive($fd, $packet_buff);
		} catch (Throwable $ex){
			$aErr = array('onReceive' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
			Main::logs($aErr , 'exErr');
		}
	}
	
	/**
	 * 处理Task异步任务
	 * @param type $serv
	 * @param type $task_id
	 * @param type $from_id
	 * @param type $data
	 */
	public function onTask($serv, $task_id, $from_id, $data){
		try {
			Main::onTask($data);
		}catch (Throwable $ex){
			$aErr = array('onTask' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
			Main::logs($aErr , 'exErr');
		}
	}
	
	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id){
		Main::onWorkerStart($serv, $worker_id);
	}
	
	public function onPipeMessage($serv, $from_worker_id, $message){
		try {
			Main::onPipeMessage($from_worker_id, $message);
		}catch (Throwable $ex){
			$aErr = array('onPipeMessage' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
			Main::logs($aErr , 'exErr');
		}
	}
	
	/**
	 * 断开连接
	 * @param type $serv
	 * @param type $fd
	 * @param type $from_id
	 */
	public function onClose($serv, $fd, $from_id){
		try {
			Main::onClose($fd);
		}catch (Throwable $ex){
			$aErr = array('onClose' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
			Main::logs($aErr , 'exErr');
		}
	}
	/**
	 * 停止了
	 */
	public function onWorkerStop($server, $worker_id){
		try {
			Main::onWorkerStop();
		}catch (Throwable $ex){
			$aErr = array('onWorkerStop' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
			Main::logs($aErr , 'exErr');
		}
	}

}
