<?php

abstract class SwooleBehavior {
	public function __construct() {
		
	}

	abstract function onReceive($server, $fd, $from_id, $data);

	public function onTask($server, $task_id, $from_id, $data) {
		
	}

	public function onFinish($serv, $task_id, $data) {
		
	}

	public function onWorkerError($serv, $worker_id, $worker_pid, $exit_code) {
		
	}

	public function onWorkerStop($server, $worker_id) {
		
	}

	public function onPipeMessage($server, $from_worker_id, $message) {
		
	}
	
	public function onPacket($server, $data, $client_info){		
	}
	
	public function onClose($server, $fd, $from_id) {
		
	}
	public function onWorkerStart($serv, $worker_id){
	}

}
