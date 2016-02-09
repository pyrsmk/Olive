<?php

namespace Olive;

use Olive\AbstractDatabase as Database;

/*
	Abstract data container
*/
abstract class AbstractDataContainer{

	/*
		Olive\AbstractDatabase $database	: database instance
		string $name						: container name
	*/
	protected $database;
	protected $name;

	/*
		Constructor

		Parameters
			Olive\AbstractDatabase $database
			mixed $name
	*/
	public function __construct(Database $database,$name){
		$this->database=$database;
		$this->name=$name;
	}

	/*
		Return a new query object

		Return
			Olive\AbstractQuery
	*/
	abstract protected function _getNewQuery();

	/*
		Return a new query object

		Return
			Olive\AbstractQuery
	*/
	public function query() {
		return $this->_getNewQuery();
	}

	/*
		Create a new query and add search parameters

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
	public function search(){
		$query=$this->_getNewQuery();
		call_user_func_array(array($query,'search'),func_get_args());
		return $query;
	}

	/*
		Insert an element

		Parameters
			array $data

		Return
			mixed
	*/
	abstract public function insert(array $data);

	/*
		Save an element

		Parameters
			array $data
	*/
	abstract public function save(array $data);

	/*
		Find rows

		Parameters 1
			string $field
			string $operator
			mixed $value

		Parameters 2
			string $id

		Parameters 3
			array $ids

		Return
			array
	*/
	public function find(){
		$query=call_user_func_array(array($this,'search'),func_get_args());
		return $query->fetch();
	}

	/*
		Find one row

		Parameters 1
			string $field
			string $operator
			mixed $value

		Parameters 2
			string $id

		Parameters 3
			array $ids

		Return
			array
	*/
	public function findOne(){
		$query=call_user_func_array(array($this,'search'),func_get_args());
		return $query->fetchOne();
	}

	/*
		Find the first value on the first row

		Parameters 1
			string $field
			string $operator
			mixed $value

		Parameters 2
			string $id

		Parameters 3
			array $ids

		Return
			array
	*/
	public function findFirst(){
		$query=call_user_func_array(array($this,'search'),func_get_args());
		return $query->fetchFirst();
	}

}
