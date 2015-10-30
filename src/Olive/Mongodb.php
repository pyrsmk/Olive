<?php

namespace Olive;

use Olive\AbstractDatabase;
use Olive\Mongodb\Collection;
use Olive\Exception;
use Olive\Exception\DatabaseError;

/*
	MongoDB adapter
*/
class Mongodb extends AbstractDatabase{

	/*
		Connect to the server and select a database

		Parameters
			string $name	: database name
			array $options	: database options
	*/
	public function __construct($name,array $options=array()){
		if(!($hosts=(array)$options['hosts'])){
			$hosts['localhost']=27017;
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
			throw new DatabaseError($e->getMessage());
		}
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
