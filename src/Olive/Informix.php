<?php

namespace Olive;

use Olive\Pdo;

/*
	Informix adapter
*/
class Informix extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		return 'informix:'.$this->_concatenateOptions($options);
	}

}
