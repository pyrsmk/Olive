<?php

namespace Olive;

use Olive\Pdo;

/*
	MySQL adapter
*/
class Mysql extends Pdo{

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : database options
	*/
	protected function _getDsn($name,$options){
		if(!isset($options['host'])){
			$options['host']='localhost';
		}
		return "mysql:dbname=$name;".$this->_concatenateOptions($options);
	}

}
