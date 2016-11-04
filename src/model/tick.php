<?php
class Tick{
	public $mainTimer;
	
	public function __construct(){
		$this->mainTimer = new Timer();
	}
	
	public function init(){
		//主业务定时器
		self::_swooleTick(500, array($this->mainTimer, 'triger'));
	}
	
	/**
	 * 添加一个延迟执行定时任务
	 * @param callbck $callback
	 * @param array $data
	 * @param int $time
	 * 
	 * @return string/null
	 */
	public function after($time, $callback, $data){
		if (!is_array($callback)){
			return null;
		}
		$tickId = $this->mainTimer->tick($callback, $data, $time);
		return $tickId;
	}
	
	
	/**
	 * 
	 * @param string $tickId
	 * @return boolean
	 */
	public function del($tickId){
		$ret = $this->mainTimer->del($tickId);
		return $ret;
	}
	
	
	private function _swooleTick($time, $callback){
		Main::$swoole->tick($time, function() use ($callback){
			try {
				call_user_func_array($callback, array());
			} catch (Throwable $th) {
				Main::logs($th, 'tickErr');
			}
		});
	}
	
}
