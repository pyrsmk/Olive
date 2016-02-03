<?php

namespace Olive;

/*
	CUBRID adapter
*/
class Cubrid extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		$host = isset($options['host']) ? $options['host'] : 'localhost';
		$port = isset($options['port']) ? $options['port'] : 33000;
		return "cubrid:host=$host;port=$port;dbname=$name";
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('cubrid', \PDO::getAvailableDrivers());
	}

}
