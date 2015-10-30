<?php

namespace Olive;

use Olive\Pdo;

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

}
