<?php

namespace Olive;

use Olive\Pdo;

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
		if($host=$options['host']){
			unset($options['host']);
		}
		else{
			$host='localhost';
		}
		// Get port
		if($port=$options['port']){
			unset($options['port']);
		}
		else{
			$port='1521';
		}
		// Generate DSN
		return "oci:dbname=//$host:$port/$name;".$this->_concatenateOptions($options);
	}

}
