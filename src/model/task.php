<?php
/**
*task任务处理类
*/
class Task{
	/**
	 * 登录
	 */
	public function userLogin($params){
		global $uid_table, $fd_table;
		
		$fd = $params['fd'];
		$uid = $params['uid'];
		$tid = $params['tid'];
		$isTableLock = $params['data']['tableLock'];
		
		
		//多重登陆判断
		$userInfo = $uid_table->get($uid);
		if (!is_null($userInfo) && $userInfo['fd'] != $fd){
			$fd_table->del($userInfo['fd']);
		}
		
		//加载桌子判断
		$table = Main::$class['dataPool']->loadTable($tid);
		if (is_null($table)){
			if ($isTableLock){
				ModelFindTable::unlockTable($tid);
				ModelFindTable::unlockUser($uid);
			}
			return ;
		}
		
		//进入桌子判断
		if (!$table->enterTable($fd, $uid)){
			if ($isTableLock){
				ModelFindTable::unlockTable($tid);
				ModelFindTable::unlockUser($uid);
			}
			return ;
		}
		
		if ($isTableLock){
			ModelFindTable::unlockTable($tid);
			ModelFindTable::unlockUser($uid);
		}
		
		$fd_table->set($fd, array('uid'=>$uid));
		$user = Main::$class['dataPool']->getUser($uid);
		
		Main::sendTcpWithUid($uid, GameConst::CMD_S_LOGIN, array('ret'=>0));
		
		$gameData = array(
				'tid'=>$tid,
				'gameSt'=>$table->status,
				'uid'=>$uid,
				'name'=>$user->mnick,
				'win'=>$user->winCount,
				'lose'=>$user->loseCount,
				'draw'=>$user->drawCount,
				'userSt'=>$table->playerList[$uid]['st'],
				'guessId'=>$table->playerList[$uid]['guessId'],
		);
		Main::sendTcpWithUid($uid, GameConst::CMD_S_GAME_DATA, $gameData);
		Main::broadcastTable($tid, GameConst::CMD_S_BROADCAST_LOGIN, array('uid'=>$uid, 'name'=>$user->mnick, 'st'=>$table->playerList[$uid]['st']));
		foreach ($table->playerList as $tmpUid=>$tmpMinfo){
			if ($tmpUid == $uid) continue;
			$tmpUser = Main::$class['dataPool']->getUser($tmpUid);
			Main::sendTcpWithUid($uid, GameConst::CMD_S_BROADCAST_LOGIN, array('uid'=>$tmpUid, 'name'=>$tmpUser->mnick, 'st'=>$table->playerList[$tmpUid]['st']));
		}
		return ;
	}
	
	/**
	 * 退出
	 */
	public function userLogout($params){
		$uid = $params['uid'];
		$tid = $params['tid'];
		$table = Main::$class['dataPool']->getTable($tid);
		if (is_null($table)){
			return ;
		}
		
		Main::sendTcpWithUid($uid, GameConst::CMD_S_LOGOUT, array());
		$table->exitTable($uid);
		return ;
	}
	
	
	/**
	 * 准备
	 * @param unknown $params
	 */
	public function ready($params){
		$uid = $params['uid'];
		$tid = $params['tid'];
		
		$table = Main::$class['dataPool']->getTable($tid);
		if (is_null($table)){
			return ;
		}
		
		$ret = $table->userReady($uid);
		Main::sendTcpWithUid($uid, GameConst::CMD_S_USER_READY, array('ret'=>$ret));
		return ;
	}
	
	/**
	 * 猜拳
	 * @param unknown $params
	 */
	public function guess($params){
		$uid = $params['uid'];
		$tid = $params['tid'];
		$guessId = $params['data']['guessId'];
		
		$table = Main::$class['dataPool']->getTable($tid);
		if (is_null($table)){
			return ;
		}
		
		$ret = $table->userGuess($uid, $guessId);
		Main::logs("user gusee, params:".json_encode($params));
		Main::sendTcpWithUid($uid, GameConst::CMD_S_USER_GUESS, array('ret'=>$ret, 'guessId'=>$guessId));
	}
	
	/**
	 * 关闭
	 */
	public function connectClose($params){
		$uid = $params['uid'];
		$tid = $params['tid'];
		
		$table = Main::$class['dataPool']->getTable($tid);
		if (is_null($table)){
			return ;
		}
		$table->exitTable($uid);
	}
	
}