<?php

namespace Olive;

/*
	MySQL adapter
*/
class Mysql extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		if(!isset($options['host'])){
			$options['host']='localhost';
		}
		return "mysql:dbname=$name;".$this->_concatenateOptions($options);
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('mysql', \PDO::getAvailableDrivers());
	}

}
