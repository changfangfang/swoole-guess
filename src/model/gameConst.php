<?php
/**
* 命令定义常量
*/
class GameConst{
	
	const CMD_C_HEARTBEAT = 0X002;				//(Client)心跳
	const CMD_C_LOGIN = 0X101;					//(Client)用户登录
	const CMD_C_LOGOUT = 0X102;					//(Client)用户退出
	const CMD_C_USER_READY = 0X103;				//(Client)用户准备
	const CMD_C_USER_GUESS = 0X104;				//(Client)用户猜拳
	
	const CMD_S_HEARTBEAT = 0X002;				//(Client)心跳
	const CMD_S_LOGIN = 0X201;					//(Server)用户登录回包
	const CMD_S_LOGOUT = 0X202;					//(Server)用户退出回包
	const CMD_S_USER_READY = 0X203;				//(Server)用户准备回包
	const CMD_S_USER_GUESS = 0X204;				//(Server)用户猜拳回包
	
	const CMD_S_GAME_DATA = 0X300;				//(Server)下发游戏初始数据
	const CMD_S_GAME_READY = 0X301;				//(Server)猜拳准备
	const CMD_S_GAME_GUESS = 0X302;				//(Server)猜拳开始
	const CMD_S_GAME_RESULT = 0X303;			//(Server)猜拳结算
	
	const CMD_S_BROADCAST_LOGIN = 0X401;		//(Server)广播房间用户登录
	const CMD_S_BROADCAST_LOGOUT = 0X402;		//(Server)广播房间用户退出
	const CMD_S_BROADCAST_USR_READY = 0X403;	//(Server)广播房间用户准备
	
	
	//
	const GAME_RESULT_WIN = 1;
	const GAME_RESULT_DRAW = 2;
	const GAME_RESULT_LOSE = 3;
}
