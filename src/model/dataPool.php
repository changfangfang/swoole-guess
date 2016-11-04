<?php
class DataPool implements IProcessData{
	
	//桌子列表
	public $tableList = array();
	
	//用户列表
	public $userList = array();
	
	
	public function __construct(){}
	
	/**
	 * 加载一个新桌子
	 * @param unknown $tid
	 * 
	 * @return Table
	 */
	public function loadTable($tid){
		if (!isset($this->tableList[$tid])){
			$this->tableList[$tid] = new Table($tid);
		}
		return $this->tableList[$tid];
	}
	
	/**
	 * 获取一个桌子
	 * @param unknown $tid
	 * 
	 * @return Table
	 */
	public function getTable($tid){
		if (!isset($this->tableList[$tid])){
			return null;
		}
		return $this->tableList[$tid];
	}
	
	/**
	 * 删除一个桌子
	 * @param unknown $tid
	 */
	public function delTable($tid){
		unset($this->tableList[$tid]);
	}
	
	
	/**
	 * 加载一个用户
	 * @param unknown $uid
	 * 
	 * @return User
	 */
	public function loadUser($uid){
		if (!isset($this->userList[$uid])){
			$this->userList[$uid] = new User($uid);
		}
		$ret = $this->userList[$uid]->loadUserInfo();
		if (!$ret){
			return false;
		}
		return $this->userList[$uid];
	}
	
	/**
	 * 获取一个用户
	 * @param unknown $uid
	 * 
	 * @return User
	 */
	public function getUser($uid){
		if (!isset($this->userList[$uid])){
			return null;
		}
		return $this->userList[$uid];
	}
	
	/**
	 * 删除一个用户
	 * @param unknown $uid
	 */
	public function delUser($uid){
		$this->userList[$uid]->saveData();
		unset($this->userList[$uid]);
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see IProcessData::getStorageData()
	 */
	public function getStorageData(){
		return array('tableList'=>$this->tableList, 'userList'=>$this->userList);
	}
	
	/**
	 * {@inheritDoc}
	 * @see IProcessData::setStorageData()
	 */
	public function setStorageData($data){
		$this->tableList = $data['tableList'];
		$this->userList = $data['userList'];
	}
}