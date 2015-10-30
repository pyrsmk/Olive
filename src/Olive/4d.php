<?php

namespace Olive;

use Olive\Pdo;

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

}
