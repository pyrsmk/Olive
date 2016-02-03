<?php

namespace Olive\Sqlite;

use Olive\Pdo\Query as PdoQuery;
use Olive\Exception;

/*
	SQLite query
*/
class Query extends PdoQuery{


	/*
		Add a search

		Parameters 1
			string $field
			string $operator
			mixed $value

		Parameters 2
			string $id

		Parameters 3
			array $ids

		Return
			Olive\AbstractQuery
	*/
	public function search() {
		if(func_num_args() == 3) {
			list($field, $operator, $value) = func_get_args();
			if($operator == 'match' || $operator == 'not match') {
				throw new Exception("'match' and 'not match' operators are disabled with SQLite adapter" );
			}
		}
		$reflector = new \ReflectionClass(get_class($this));
		$parent = $reflector->getParentClass();
		$method = $parent->getMethod('search');
		return $method->invokeArgs($this, func_get_args());
	}
	
}