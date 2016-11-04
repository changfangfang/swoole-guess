<?php
/**
 * 进程数据加载器， 用于备份和加载进程内数据
 */
class DataLoader{

	private static $objList = array();


	public static function registerObject(IProcessData $obj){
		$className = get_class($obj);
		self::$objList[$className] = $obj;
	}


	public static function save(){
		$data = array();
		foreach (self::$objList as $className=>$obj){
			$data[$className] = $obj->getStorageData();
		}
		self::_saveStorage($data);
	}


	public static function load(){
		$data = self::_getStorage();
		foreach (self::$objList as $className=>$obj){
			if (!isset($data[$className])){
				continue;
			}
			$obj->setStorageData($data[$className]);
		}
	}


	private static function _saveStorage(array $data){
		$dataStar = serialize($data);
		
		$fileDirPath = self::_getDataDirPath();
		if (!is_dir($fileDirPath)){
			mkdir($fileDirPath, 0777, true);
		}
		
		$filePath = self::_getFilePath();
		@file_put_contents($filePath, $dataStar);
	}


	private static function _getStorage(){
		$filePath = self::_getFilePath();
		if (!file_exists($filePath)){
			return array();
		}
		
		$contentStr = @file_get_contents($filePath);
		if (false == $contentStr){
			return array();
		}
		
		$aContent = @unserialize($contentStr);
		if (!is_array($aContent)){
			return array();
		}
		
		rename($filePath, $filePath . '.bak');
		
		return $aContent;
	}

	private static function _getFilePath(){
		return self::_getDataDirPath() . self::_getFileName();
	}
	
	private static function _getDataDirPath(){
		return SERVER_ROOT . '/data/';
	}
	
	private static function _getFileName(){
		$woerId = Main::$swoole->worker_id;

		global $crontab_work_table;
		$ver = $crontab_work_table->incr($woerId, 'ver', 0);
		
		return "v{$ver}_woker{$woerId}.data";
	}
	
	
}