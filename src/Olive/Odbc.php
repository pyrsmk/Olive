<?php

namespace Olive;

use Olive\Pdo;

/*
	ODBC adapter
*/
class Odbc extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
	   return 'odbc:'.$this->_concatenateOptions($options);
	}

}
