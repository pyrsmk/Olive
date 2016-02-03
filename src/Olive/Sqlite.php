<?php

namespace Olive;

use Olive\Sqlite\Table;

/*
	SQLite adapter
*/
class Sqlite extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		if(isset($options['sqlite2'])) {
			return 'sqlite2:'.$name;
		}
		else{
			return 'sqlite:'.$name;
		}
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('sqlite', \PDO::getAvailableDrivers());
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\Sqlite\Table
	*/
	public function getDataContainer($name){
		return new Table($this,$name);
	}

	/*
		Return all container names

		Return
			array
	*/
	public function getDataContainerNames(){
		// Retrieve schema informations
		try{
			$query=$this->driver->query('SELECT name FROM sqlite_master WHERE type="table" AND name<>"sqlite_sequence"');
			$results=$query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
		// Clean up
		$names=array();
		foreach($results as $result){
			$names[]=$result['name'];
		}
		return $names;
	}

}
