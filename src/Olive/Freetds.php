<?php

namespace Olive;

use Olive\Pdo;

/*
	FreeTDS adapter
*/
class Freetds extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return "dblib:database=$name;".$this->_concatenateOptions($options);
	}

}
