<?php

namespace Olive;

use Olive\Mongodb\Collection;

/*
	MongoDB adapter
*/
class Mongodb extends AbstractDatabase {
	
	/*
		Initialize the database
		
		Parameters
			string $name	: database name
			array $options	: database options
	*/
	protected function _initDatabase($name, $options) {
		$this->name = $name;
		if(!isset($options['hosts'])) {
			$options['hosts'] = array('localhost' => 27017);
		}
		try{
			// Generate host list
			$servers = array();
			foreach($options['hosts'] as $host => $port) {
				$servers[] = $host.':'.$port;
			}
			unset($options['hosts']);
			// Generate auth chain
			if($options['username'] && $options['password']) {
				$auth = urlencode(trim($options['username'])).':'.urlencode(trim($options['password'])).'@';
			}
			unset($options['username']);
			unset($options['password']);
			// Instantiate driver
			$this->driver = new \MongoDB\Driver\Manager('mongodb://'.$auth.implode(',',$servers).'/'.urlencode($name),$options);
		}
		catch(\Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('mongodb');
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\Mongo\Collection
	*/
	public function getDataContainer($name) {
		return new Collection($this, $this->name.'.'.$name);
	}

	/*
		Return all container names

		Return
			array
	*/
	public function getDataContainerNames() {
		$names = array();
		$results = $this->driver->executeCommand(
			$this->name,
			new \MongoDB\Driver\Command(['listCollections' => 1])
		);
		foreach($results->toArray() as $collection) {
			$names[] = $collection;
		}
		return $names;
	}

}
