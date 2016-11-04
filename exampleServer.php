<?php
define('SERVER_ROOT', dirname(__FILE__).'/');

if ($_SERVER['argc'] != 2){
	echo "Usage: php {$argv[0]} port\n";
	exit(-1);
}

$argv = $_SERVER['argv'];
define('SWOOLE_PORT', intval($argv[1])); //1:监听端口

define('CFG_ROOT', SERVER_ROOT.'cfg/');
define('LIB_ROOT', SERVER_ROOT.'src/lib/');
define('MOD_ROOT', SERVER_ROOT.'src/model/');

include LIB_ROOT . 'SwooleService.php';

error_reporting(E_ALL);
$SwooleConfig = include CFG_ROOT.'swoole.php';//加载配置
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = SWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("exampleBehivor", SERVER_ROOT . 'exampleBehivor.php');
$exampleService = new SwooleService($SwooleConfig);

function processEnd(){
	$error = error_get_last();
	$error['date'] = date('Y-m-d H:i:s');
	$error['info'] = debug_backtrace();
	Main::logs($error, 'runEndErr');
	Main::processEnd();
}

function error_handler($errno, $errstr, $errfile, $errline){
	$error = '';
	$error .= date( 'Y-m-d H:i:s') . '--';
	$error .= 'Type:' . $errno . '--';
	$error .= 'Msg:' . $errstr . '--';
	$error .= 'File:' . $errfile . '--';
	$error .= 'Line:' . $errline . '--';
	Main::logs($error, 'handlerErr');
}

set_error_handler( 'error_handler', E_ALL ^ E_NOTICE); //注册错误函数 E_WARNING|E_ERROR
register_shutdown_function("processEnd");

//以uid为key记录用户信息
global $uid_table;
$uid_table = new swoole_table(16384);
$uid_table->column('fd', swoole_table::TYPE_INT, 4);
$uid_table->column('tid', swoole_table::TYPE_INT, 4);
$uid_table->create();

//以fd为key 找到对应的uid
global $fd_table;
$fd_table = new swoole_table(16384);
$fd_table->column('uid', swoole_table::TYPE_INT, 4);
$fd_table->create();


//用于记录 进程启动时间
global $crontab_work_table;
$crontab_work_table = new swoole_table(32);
$crontab_work_table->column('ver', swoole_table::TYPE_INT, 4);//目前版本号
$crontab_work_table->create();

//记录最大桌子id
global $tid_atomic;
$tid_atomic = new swoole_atomic(1);

//用于记录桌子信息
global $game_table;
$game_table = new swoole_table(4096);
$game_table->column('play', swoole_table::TYPE_INT, 1); 	//在玩人数
$game_table->create();

//桌子锁（找桌子时使用）
global $tlock_table;
$tlock_table = new swoole_table(4096);
$tlock_table->column('lock', swoole_table::TYPE_INT, 4);
$tlock_table->create();

//用户锁(找桌子时使用)
global $uid_lock_table;
$uid_lock_table = new swoole_table(16384);
$uid_lock_table->column('lock', swoole_table::TYPE_INT, 4);
$uid_lock_table->create();


$exampleService->Start();
