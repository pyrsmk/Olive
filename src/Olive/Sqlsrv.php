<?php

namespace Olive;

/*
	MS SQL Server / SQL Azure adapter
*/
class Sqlsrv extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return "sqlsrv:database=$name;".$this->_concatenateOptions($options);
	}
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	static public function isSupported() {
		return extension_loaded('pdo') && in_array('sqlsrv', \PDO::getAvailableDrivers());
	}

}
