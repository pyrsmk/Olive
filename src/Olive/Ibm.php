<?php

namespace Olive;

/*
	IBM adapter
*/
class Ibm extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		if(!$options){
			return 'ibm:dsn='.$name;
		}
		else{
			return "ibm:database=$name;".$this->_concatenateOptions($options);
		}
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('ibm', \PDO::getAvailableDrivers());
	}

}
