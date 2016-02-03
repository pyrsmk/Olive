<?php

namespace Olive;

use Olive\Mongodb\Collection;

/*
	MongoDB adapter
*/
class Mongodb extends AbstractDatabase{
	
	/*
		Initialize the database
		
		Parameters
			string $name	: database name
			array $options	: database options
	*/
	protected function _initDatabase($name, $options) {
		if(!isset($options['hosts'])){
			$hosts = array('localhost' => 27017);
		}
		try{
			// Generate host list
			$servers=array();
			foreach($hosts as $host=>$port){
				$servers[]=$host.':'.$port;
			}
			unset($options['hosts']);
			// Generate auth chain
			if(($username=$options['username']) && ($password=$options['password'])){
				$auth=urlencode(trim($username)).':'.urlencode(trim($password)).'@';
			}
			unset($options['username']);
			unset($options['password']);
			// Instantiate driver
			$mongo=new \MongoClient('mongodb://'.$auth.implode(',',$servers).'/'.urlencode($name),$options);
			// Select database
			$this->driver=$mongo->$name;
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('mongo');
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\Mongo\Collection
	*/
	public function getDataContainer($name){
		return new Collection($this,$name);
	}

	/*
		Return all container names

		Return
			array
	*/
	public function getDataContainerNames(){
		$names=array();
		foreach($this->driver->listCollections() as $collection){
			$names[]=(string)$collection;
		}
		return $names;
	}

}
