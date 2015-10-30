<?php

namespace Olive;

use Olive\Pdo;

/*
	Sybase adapter
*/
class Sybase extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return "sybase:database=$name;".$this->_concatenateOptions($options);
	}

}
