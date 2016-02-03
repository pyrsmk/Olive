<?php

namespace Olive;

/*
	4D adapter
*/
class 4d extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return '4D:'.$this->_concatenateOptions($options);
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('4D', \PDO::getAvailableDrivers());
	}

}
