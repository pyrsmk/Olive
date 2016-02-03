<?php

namespace Olive;

use Olive\AbstractDatabase as Database;

/*
	Abstract query
*/
abstract class AbstractQuery{

	/*
		Olive\AbstractDatabase $database	: database instance
		string $name						: container name
		array $query						: the query
	*/
	protected $database;
	protected $name;
	protected $query=array(
		'search' => array(),
		'select' => array(),
		'sort' => array(),
		'join' => array(),
		'limit' => null,
		'skip' => null
	);

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
	public function search(){
		switch(func_num_args()){
			// Search all
			case 0:
				break;
			// IDs
			case 1:
				$spec=func_get_arg(0);
				if(is_string($spec)){
					$operator='is';
				}
				else if(is_array($spec)){
					$operator='in';
				}
				else{
					throw new Exception("The argument should be a string or an array");
				}
				$this->query['search'][]=array(array(
					'field' => '_id',
					'operator' => $operator,
					'value' => $spec
				));
				break;
			// Basic query
			case 3:
				$args=func_get_args();
				$operator=strtolower((string)$args[1]);
				switch($operator){
					case 'is':
					case 'is not':
					case 'greater':
					case 'less':
						break;
					case 'in':
					case 'not in':
						if(!is_array($args[2])){
							throw new Exception("'in' and 'not in' operators require an array");
						}
						break;
					case 'like':
					case 'not like':
					case 'match':
					case 'not match':
						break;
					default:
						throw new Exception("Invalid '$operator' operator");
				}
				$this->query['search'][]=array(array(
					'field' => (string)$args[0],
					'operator' => $operator,
					'value' => $args[2]
				));
				break;
			// Invalid query
			default:
				throw new Exception("Invalid number of arguments");
		}
		return $this;
	}

	/*
		Add an OR search

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
	public function orSearch(){
		// Create search
		call_user_func_array(array($this,'search'),func_get_args());
		$search=array_pop($this->query['search']);
		$search=$search[0];
		// Verify if there's at least a non-empty registered search
		if(!count($this->query['search']) || !end($this->query['search'])){
			throw new Exception("There's no valid registered search, cannot call 'searchOr()'");
		}
		// Add the OR search
		$this->query['search'][key($this->query['search'])][]=$search;
		return $this;
	}

	/*
		Join data from two different containers

		Parameters
			string $field1: first field to join
			string $field2: second field to join

		Return
			Olive\AbstractQuery
	*/
	public function join($field1,$field2){
		list($container1,$field1)=explode('.',$field1);
		list($container2,$field2)=explode('.',$field2);
		if(!$field1 || !$field2){
			throw new Exception("Invalid join query");
		}
		$this->query['join'][]=array(
			'container1' => $container1,
			'field1' => $field1,
			'container2' => $container2,
			'field2' => $field2
		);
		return $this;
	}

	/*
		Select a field to retrieve

		Parameters
			string $field   : field to retrieve
			string $alias   : an alias for that field

		Return
			Olive\AbstractQuery
	*/
	public function select($field,$alias=''){
		$this->query['select'][]=array(
			'field' => (string)$field,
			'alias' => (string)$alias
		);
		return $this;
	}

	/*
		Sort elements

		Parameters
			string $field : field to sort
			string $order : 'asc' or 'desc'

		Return
			Olive\AbstractQuery
	*/
	public function sort($field,$order='asc'){
		$this->query['sort'][]=array(
			'field' => (string)$field,
			'order' => strtolower((string)$order)
		);
		return $this;
	}

	/*
		Limit results

		Parameters
			integer $number : number of results to return

		Return
			Olive\AbstractQuery
	*/
	public function limit($number){
		$this->query['limit']=(int)$number;
		return $this;
	}

	/*
		Skip results

		Parameters
			integer $number : number of results to skip

		Return
			Olive\AbstractQuery
	*/
	public function skip($number){
		$this->query['skip']=(int)$number;
		return $this;
	}

	/*
		Update an element

		Parameters
			array $data
	*/
	abstract public function update(array $data);

	/*
		Remove an element
	*/
	abstract public function remove();

	/*
		Return the number of elements for that query

		Return
			integer
	*/
	abstract public function count();

	/*
		Fetch all results

		Parameters
			array $options : driver options

		Return
			array
	*/
	abstract public function fetch();

	/*
		Fetch first result

		Parameters
			array $options : driver options

		Return
			array
	*/
	abstract public function fetchOne();

	/*
		Fetch first field of the first result

		Parameters
			array $options : driver options

		Return
			mixed
	*/
	abstract public function fetchFirst();

}