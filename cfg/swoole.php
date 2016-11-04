<?php

/**
 * swoole 相关配置
 */
return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'worker_num' => 3,
		'dispatch_mode' => 3,
		'task_worker_num' => 3,
		'max_request'=>0,
		'task_ipc_mode' => 2,
		'task_max_request'=>0,
		'message_queue_key'=>65535+SWOOLE_PORT, //把常量去掉会报错
		
		'open_eof_split' => 1,
		'package_eof' => "@@",
		
		'heartbeat_check_interval'=>10,
		'heartbeat_idle_time'=>30,
		'discard_timeout_request'=>true,
		'enable_unsafe_event' => true,
	)
);
