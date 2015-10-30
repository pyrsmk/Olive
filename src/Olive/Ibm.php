<?php

namespace Olive;

use Olive\Pdo;

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

}
