<?php

namespace Olive;

use Olive\Pdo;

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

}
