<?php
/**
*	定时器类
*/
class Timer implements IProcessData{
	
	/**
	 * @var $tickList  tick列表
	 * 数据格式
	 * {
	 * 	tickId:{
	 * 		callback: callback, 	//回调
	 * 		data: data, 			//回调参数
	 * 		excuteTime: excuteTime 	//下次执行时间
	 * 		count: count, 			//剩余执行次数
	 * 		interval: interval		//重复执行间隔
	 * 	},
	 *  ...
	 * }
	 */
	private $tickList = array();
	
	
	public function triger(){
		$currentTime = time();
		foreach ($this->tickList as $tickId=>$tickInfo){
			if ($currentTime < $tickInfo['excuteTime']){
				continue;
			}
			if ($tickInfo['count'] == 0){
				$this->del($tickId);
				continue;
			}
			
			try {
				call_user_func_array($tickInfo['callback'], $tickInfo['data']);
			} catch (Throwable $th) {
				
			}
			$this->tickList[$tickId]['excuteTime'] += $this->tickList[$tickId]['interval'];
			$this->tickList[$tickId]['count'] -= 1;
		}
	}
	
	
	/**
	 * 添加一个定时执行任务
	 * @param $callback	回调
	 * @param $data	回调函数参数
	 * @param $after	多少秒后首次执行(单位:S), 
	 * @param $repeatCount	重复执行次数
	 * @param $interval	每次重复间隔时间(单位:S)
	 * 
	 * @return $tickId	定时id
	 */
	public function tick($callback, array $data, $after=0, $repeatCount=1, $interval=0){
		$currentTime = time();
		$tickId = $this->_genTickId();
		$this->tickList[$tickId] = array(
				'callback'=>$callback,
				'data'=>$data,
				'excuteTime'=>$currentTime + $after,
				'count'=>$repeatCount,
				'interval'=>$interval
		);
		return $tickId;
	}
	
	/**
	 * 删除一个定时任务
	 * @param $tickId	定时id
	 */
	public function del($tickId){
		if (!isset($this->tickList[$tickId])){
			return false;
		}
		unset($this->tickList[$tickId]);
		return true;
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see IProcessData::getStorageData()
	 */
	public function getStorageData(){
		return array('tickList'=>$this->tickList);
	}
	
	/**
	 * {@inheritDoc}
	 * @see IProcessData::setStorageData()
	 */
	public function setStorageData($data){
		foreach ($data['tickList'] as $tickId=>$tickInfo){
			$this->tickList[$tickId] = $tickInfo;
		}
	}
	
	//生成唯一id
	private function _genTickId(){
		return md5(uniqid("", true));
	}
	
}
