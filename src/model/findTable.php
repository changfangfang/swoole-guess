<?php
/**
* 查询一个房间
*/
class ModelFindTable{
	
	const MAX_TABLE_PLAYER_COUNT = 2;
	
	/**
	 * 找一个可用的桌子id
	 */
	public static function find($uid){
		if (!self::lockUser($uid)){
			return null;
		}
		
		$tid = self::_findTable();
		if (is_null($tid)){
			$tid = self::_addTable();
			self::lockTable($tid);
		}
		return $tid;
	}
	
	/**
	 * 对指定桌子加锁（找桌子时使用）
	 * @param unknown $tid
	 */
	public static function lockTable($tid){
		global $tlock_table;
		$lock = $tlock_table->incr($tid, 'lock', 1);
		return $lock;
	}
	
	/**
	 * 对指定桌子解锁（找桌子时使用）
	 * @param unknown $tid
	 */
	public static function unlockTable($tid){
		global $tlock_table;
		$lock = $tlock_table->decr($tid, 'lock', 1);
		if ($lock < 0){
			$tlock_table->set($tid, array('lock'=>0));
			Main::logs("unlock table failed, tid:{$tid}, lock:{$lock}", 'tableErr');
		}
	}
	
	/**
	 * 对指定用户加锁，同时只允许一个用户找桌子（找桌子时使用）
	 * @param unknown $uid
	 */
	public static function lockUser($uid){
		global $uid_lock_table;
		$lock = $uid_lock_table->incr($uid, 'lock', 1);
		if ($lock != 1){
			return false;
		}
		return true;
	}
	
	/**
	 * 对指定用户解锁（找桌子时使用）
	 * @param unknown $uid
	 */
	public static function unlockUser($uid){
		global $uid_lock_table;
		$uid_lock_table->set($uid, array('lock'=>0));
		$uid_lock_table->del($uid);
	}
	
	
	/**
	 * 跟新桌子人数（找桌子时使用）
	 * @param unknown $tid
	 * @param unknown $count
	 */
	public static function updateTablePlayerCount($tid, $count){
		global $game_table;
		$game_table->set($tid, array('play'=>$count));
	}
	
	//打印调试信息
	public static function debugAllTable(){
		return ;
	}
	
	
	private static function _findTable(){
		global $tid_atomic, $game_table;
	
		$availTid = null;
		$playerCount = -1;
	
		$maxTid = $tid_atomic->get();
		for ($tid=1; $tid<=$maxTid; $tid++){
			$tmpCount = $game_table->incr($tid, 'play', 0);
			if ($tmpCount >= self::MAX_TABLE_PLAYER_COUNT) {
				continue;
			}
			if ($tmpCount <= $playerCount){
				continue;
			}
			if (self::lockTable($tid) + $tmpCount > self::MAX_TABLE_PLAYER_COUNT){
				self::unlockTable($tid);
				continue;
			}
			if (!is_null($availTid)){
				self::unlockTable($availTid);
			}
			
			$availTid = $tid;
			$playerCount = $tmpCount;
			if ($playerCount == self::MAX_TABLE_PLAYER_COUNT-1){
				break;
			}
		}
		
		return $availTid;
	}
	
	private static function _addTable(){
		global $tid_atomic, $game_table;
		$tid = $tid_atomic->add();
		$game_table->set($tid, array('play'=>0));
		return $tid;
	}
	
}