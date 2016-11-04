<?php
/**
* 桌子相关类
*/
class Table{
	
	const GUESS_ID_SHI_TOU = 1;		//石头
	const GUESS_ID_JIAN_DAO = 2;	//剪刀
	const GUESS_ID_BU = 3;			//布
	
	const GAME_ST_READY = 1;
	const GAME_ST_GUESS = 2;
	const GAME_ST_RESULT = 3;
	
	const USER_ST_WAITING = 1;
	const USER_ST_READY = 2;
	
	public $tid;
	
	public $status;
	
	public $playerList;
	
	public $guessTimer = null;
	
	public function __construct($tid){
		$this->tid = $tid;
		$this->status = self::GAME_ST_READY;
		$this->playerList = array();
	}
	
	//用户进入桌子
	public function enterTable($fd, $uid){
		global $uid_table;
		$user = Main::$class['dataPool']->loadUser($uid);
		if (!$user){
			return false;
		}
		
		if (!isset($this->playerList[$uid])){
			$this->playerList[$uid] = array('st'=>self::USER_ST_WAITING, 'guessId'=>null);
		}
		
		$uid_table->set($uid, array('fd'=>$fd, 'tid'=>$this->tid, 'lastOpTime'=>time()));
		ModelFindTable::updateTablePlayerCount($this->tid, count($this->playerList));
		
		return true;
	}
	
	
	//用户退出桌子
	public function exitTable($uid){
		global $uid_table;
		
		Main::broadcastTable($this->tid, GameConst::CMD_S_BROADCAST_LOGOUT, array('uid'=>$uid));
		
		unset($this->playerList[$uid]);
		Main::$class['dataPool']->delUser($uid);
		$uid_table->del($uid);
		
		ModelFindTable::updateTablePlayerCount($this->tid, count($this->playerList));
		if (empty($this->playerList)){
			Main::$class['dataPool']->delTable($this->tid);
		}
	}
	
	
	//用户准备
	public function userReady($uid){
		if ($this->status != self::GAME_ST_READY){
			return false;
		}
		
		if (!isset($this->playerList[$uid])){
			return false;
		}
		
		$this->playerList[$uid]['st'] = self::USER_ST_READY;
		
		$isGuess = 0;
		$userCount = count($this->playerList);
		if ($userCount>=2 && $this->_checkAllUserReady()){
			$this->gameGuess();
			$isGuess = 1;
		}
		
		Main::broadcastTable($this->tid, GameConst::CMD_S_BROADCAST_USR_READY, array('uid'=>$uid));
		Main::logs("User Ready, player list:".json_encode($this->playerList) . ", isGuess:{$isGuess}");
		return true;
	}
	
	
	//用户猜拳
	public function userGuess($uid, $guessId){
		if ($this->status != self::GAME_ST_GUESS){
			Main::logs("user guess error, 1");
			return false;
		}
		
		if (!isset($this->playerList[$uid])){
			Main::logs("user guess error, 2");
			return false;
		}
		
		if ($this->playerList[$uid]['guessId'] != null){
			Main::logs("user guess error, 3");
			return false;
		}
		
		$this->playerList[$uid]['guessId'] = $guessId;
		
		if ($this->_checkAllUserGuess()){
			$this->gameResult();
			Main::$class['tick']->del($this->guessTimer);
		}
		
		return true;
	}
	
	
	//游戏准备
	public function gameReady(){
		$this->status = self::GAME_ST_READY;
		foreach ($this->playerList as $uid=>$userInfo){
			$this->playerList[$uid]['st'] = self::USER_ST_WAITING;
			$this->playerList[$uid]['guessId'] = null;
		}
		Main::broadcastTable($this->tid, GameConst::CMD_S_GAME_READY, array());
		Main::logs("Game Ready, players:".json_encode($this->playerList));
	}
	
	
	//游戏猜拳
	public function gameGuess(){
		$this->status = self::GAME_ST_GUESS;
		//下发给客户端， 开始猜拳
		$this->guessTimer = Main::$class['tick']->after(8, array($this, 'gameResult'), array());
		Main::broadcastTable($this->tid, GameConst::CMD_S_GAME_GUESS, array());
	}
	
	
	//游戏结算
	public function gameResult(){
		$this->status = self::GAME_ST_RESULT;
		
		$guessList = array();
		foreach ($this->playerList as $uid=>$userInfo){
			if ($userInfo['guessId'] == null){
				$this->playerList[$uid]['guessId'] = self::_getRandomGuessId();
				Main::sendTcpWithUid($uid, GameConst::CMD_S_USER_GUESS, array('ret'=>true, 'guessId'=>$this->playerList[$uid]['guessId']));
			}
			$guessList[$uid] = $this->playerList[$uid]['guessId'];
		}
		
		$result = self::_compareAll($guessList);
		var_export($result);
		var_export($guessList);
		foreach ($result as $uid=>$ret){
			$user = Main::$class['dataPool']->getUser($uid);
			if (is_null($user)){
				Main::logs("---user is null, uid:{$uid}----------");
				continue;
			}
			$user->updateResult($ret);
			
			$otherGuessId = self::GUESS_ID_BU;
			foreach ($this->playerList as $tmpUid=>$tmpInfo){
				if ($tmpUid == $uid) continue;
				$otherGuessId = $tmpInfo['guessId'];
				break;
			}
			
			Main::sendTcpWithUid($uid, GameConst::CMD_S_GAME_RESULT, array('ret'=>$ret, 'other'=>$otherGuessId, 'win'=>$user->winCount, 'lose'=>$user->loseCount, 'draw'=>$user->drawCount));
		}
		
		
		Main::$class['tick']->after(5, array($this, 'gameReady'), array());
	}
	
	
	//---------------------------------------------------------------
	//检查是否所有用户已经准备
	private function _checkAllUserReady(){
		$isReady = true;
		foreach ($this->playerList as $uid=>$userInfo){
			if ($userInfo['st'] != self::USER_ST_READY){
				$isReady = false;
				break;
			}
		}
		return $isReady;
	}
	
	private function _checkAllUserGuess(){
		$isGuss = true;
		foreach ($this->playerList as $uid=>$userInfo){
			if ($userInfo['guessId'] == null){
				$isGuss = false;
				break;
			}
		}
		Main::logs("-check user guess, {$isGuss}---------");
		return $isGuss;
	}
	
	//随机一个结果
	private static function _getRandomGuessId() {
		$list = array(self::GUESS_ID_SHI_TOU, self::GUESS_ID_JIAN_DAO, self::GUESS_ID_BU);
		shuffle($list);
		return array_shift($list);
	}
	
	
	private static function _compareAll($guessList){
		$res = array();
		$guessTypeList = array_values(array_unique($guessList));
		$guessTypeCount = count($guessTypeList);
		if ($guessTypeCount != 2){
			foreach ($guessList as $uid=>$guessId){
				$res[$uid] = GameConst::GAME_RESULT_DRAW;
			}
		}else{
			$ret0 = self::_compare($guessTypeList[0], $guessTypeList[1]);
			$ret1 = self::_compare($guessTypeList[1], $guessTypeList[0]);
			foreach ($guessList as $uid=>$guessId){
				if ($guessId == $guessTypeList[0]){
					$res[$uid] = $ret0;
					continue;
				}
				if ($guessId == $guessTypeList[1]){
					$res[$uid] = $ret1;
					continue;
				}
			}
		}
		return $res;
	}
	
	private static function _compare($guessId1, $guessId2){
		if ($guessId1 == self::GUESS_ID_SHI_TOU && $guessId2 == self::GUESS_ID_BU){
			return -1;
		}
		if ($guessId1 == self::GUESS_ID_SHI_TOU && $guessId2 == self::GUESS_ID_JIAN_DAO){
			return 1;
		}
		if ($guessId1 == self::GUESS_ID_JIAN_DAO && $guessId2 == self::GUESS_ID_SHI_TOU){
			return -1;
		}
		if ($guessId1 == self::GUESS_ID_JIAN_DAO && $guessId2 == self::GUESS_ID_BU){
			return 1;
		}
		if ($guessId1 == self::GUESS_ID_BU && $guessId2 == self::GUESS_ID_JIAN_DAO){
			return -1;
		}
		if ($guessId1 == self::GUESS_ID_BU && $guessId2 == self::GUESS_ID_SHI_TOU){
			return 1;
		}
		return 0;
	}
	
}
