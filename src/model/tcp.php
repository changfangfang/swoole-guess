<?php
/**
*处理客户端请求数据
*/
class Tcp{
	private $fd;
	
	public function init($fd, $data){
		$method = sprintf("tcp_0x%'03X", $data['cmd']);
		if(method_exists($this, $method)){
			$this->fd = $fd;
			call_user_func_array(array($this, $method), array($data['data']));
		}else{
			Main::logs($method. ' not exists' , 'tcpErr');
		}
	}
	
	//登陆游戏
	public function tcp_0x101($params){
		$uid = $params['uid'];
		
		$tableLock = false;
		
		global $uid_table;
		$userInfo  = $uid_table->get($uid);
		if ($userInfo){
			$tid = $userInfo['tid'];
		}else {
			$tid = ModelFindTable::find($uid);
			$tableLock = true;
		}
		
		if (is_null($tid)){
			return ;
		}
		
		Main::task(Main::getTableTaskId($tid), 'userLogin', array('fd'=>$this->fd, 'uid'=>$uid, 'tid'=>$tid, 'data'=>array('tableLock'=>$tableLock)));
	}
	
	//退出游戏
	public function tcp_0x102($params){
		$this->_doUserTask('userLogout', $params);
	}
	
	//准备
	public function tcp_0x103($params){
		$this->_doUserTask('ready', $params);
	}
	
	//猜拳
	public function tcp_0x104($params){
		$this->_doUserTask('guess', $params);
	}
	
	//心跳
	public function tcp_0x002($params){
		Main::sendTcp($this->fd, GameConst::CMD_S_HEARTBEAT, array());
	}
	
	
	private function _doUserTask($method, $data){
		global $fd_table, $uid_table;
		$fdInfo = $fd_table->get($this->fd);
		if (null == $fdInfo){
			return ;
		}
		$userInfo = $uid_table->get($fdInfo['uid']);
		if (null == $userInfo){
			return ;
		}
		
		Main::task(Main::getTableTaskId($userInfo['tid']), $method, array('fd'=>$this->fd, 'uid'=>$fdInfo['uid'], 'tid'=>$userInfo['tid'], 'data'=>$data));
	}
	
}