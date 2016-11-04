<?php

class User{
	public $uid;	//用户id
	
	public $mnick;	//用户名
	
	public $winCount;	//胜的次数
	
	public $drawCount;	//平的次数
	
	public $loseCount;	//输的次数
	
	public function __construct($uid){
		$this->uid = $uid;
	}
	
	/**
	 * 用户结果猜拳更新
	 * @param unknown $result
	 */
	public function updateResult($result){
		if ($result == GameConst::GAME_RESULT_WIN){
			$this->winCount++;
		}elseif ($result == GameConst::GAME_RESULT_DRAW){
			$this->drawCount++;
		}else {
			$this->loseCount++;
		}
		return ;
	}
	
	/**
	 * 加载用户基础数据
	 */
	public function loadUserInfo(){
		$this->mnick = 'User_'.$this->uid;
		$this->winCount = 0;
		$this->drawCount = 0;
		$this->loseCount = 0;
		return true;
	}
	
	public function saveData(){
		//用户数据落地
	}
}