<?php

namespace Olive;

/*
	Oracle adapter
*/
class Oracle extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		// Get host
		if(isset($options['host'])){
			$host = $options['host'];
			unset($options['host']);
		}
		else{
			$host = 'localhost';
		}
		// Get port
		if(isset($options['port'])) {
			$port = $options['port'];
			unset($options['port']);
		}
		else{
			$port = '1521';
		}
		// Generate DSN
		return "oci:dbname=//$host:$port/$name;".$this->_concatenateOptions($options);
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('oci', \PDO::getAvailableDrivers());
	}

}
