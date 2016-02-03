<?php

namespace Olive;

/*
	Microsoft SQL Server adapter
*/
class Mssql extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return "mssql:database=$name;".$this->_concatenateOptions($options);
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('mssql', \PDO::getAvailableDrivers());
	}

}
