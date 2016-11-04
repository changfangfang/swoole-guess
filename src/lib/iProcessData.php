<?php
/**
 * 内存数据接口
 */
interface IProcessData{
	/**
	 * 获取对象需要保存的数据
	 */
	public function getStorageData();
	
	/**
	 * 设置从存储中获取的对象数据
	 * @param unknown $data
	 */
	public function setStorageData($data);
}