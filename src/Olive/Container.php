<?php

namespace Olive;

use Olive\Database;
use Olive\Exception;
use Olive\Cache;
use Iterator;
use Closure;

/*
	Abstract data container

	Author
		Aurélien Delogu (dev@dreamysource.fr)
*/
abstract class Container implements Iterator{

	/*
		Olive\Database $olive   : Olive instance
		string $name            : container name
		Closure $cache          : cache object
		array $search_request   : search request
		array $select_request   : select request
		array $sort_request     : sort request
		integer $limit_request  : limit request
		integer $skip_request   : skip request
		array $filters 			: filter list
		array $validators		: validator list
	*/
	protected $olive;
	protected $name;
	protected $cache;
	protected $search_request=array();
	protected $select_request=array();
	protected $sort_request=array();
	protected $limit_request;
	protected $skip_request;
	protected $filters=array();
	protected $validators=array();

	/*
		Constructor

		Parameters
			Olive\Database $olive
			mixed $name
			Olive\Cache $cache
	*/
	final public function __construct($olive,$name,Closure $cache){
		$this->olive=$olive;
		$this->name=$name;
		$this->cache=$cache;
		$this->_init();
	}

	/*
		Initialize container
	*/
	protected function _init(){}

	/*
		Log a request

		Parameters
			array $data

		Throw
			Olive\Exception: if a callback is not callable
	*/
	final protected function _log(array $data){
		foreach(Database::$log as $callback){
			if(!is_callable($callback)){
				throw new Exception("A callback is not callable, logging aborted");
			}
			call_user_func($callback,$data);
		}
	}

	/*
		Verify if search() has been called

		Throw
			Olive\Exception
	*/
	final protected function _verifySearchRequest(){
		if($this->search_request===null){
			throw new Exception("search() method must be called before any other request method");
		}
	}

	/*
		Search rows

		Return
			Olive\Container

		Throw
			Olive\Exception     : if the field parameter is null, not a string nor an array
			Olive\Exception     : if the operator parameter is null or not a string
	*/
	final public function search(){
		switch(func_num_args()){
			// Search all
			case 0:
				$this->search_request=array();
				break;
			// OR queries
			case 1:
				$queries=func_get_arg(0);
				if(!is_array($queries)){
					throw new Exception("Invalid argument, array expected");
				}
				$this->search_request[]=$queries;
				break;
			// Basic query
			case 3:
				$this->search_request[]=func_get_args();
				break;
			// Invalid request
			default:
				throw new Exception("Invalid number of arguments");
		}
		return $this;
	}

	/*
		Select a field to retrieve

		Parameters
			string $field   : field to retrieve
			string $alias   : alias for that field

		Return
			Olive\Container
	*/
	final public function select($field,$alias=null){
		$this->_verifySearchRequest();
		if(is_array($field)){
			$this->select_request=array_merge((array)$this->select_request,$field);
		}
		else if($alias){
			$this->select_request[(string)$alias]=$field;
		}
		else{
			$this->select_request[]=$field;
		}
		return $this;
	}

	/*
		Sort elements

		Parameters
			array $fields   : fields by which to sort

		Return
			Olive\Container

		Throw
			Olive\Exception : if find() was not called
	*/
	final public function sort($field,$order){
		$this->_verifySearchRequest();
		$this->sort_request[(string)$field]=$order;
		return $this;
	}

	/*
		Limit results

		Parameters
			integer $number : number of results to return

		Return
			Olive\Pdo\Table
	*/
	final public function limit($number){
		$this->_verifySearchRequest();
		$this->limit_request=(int)$number;
		return $this;
	}

	/*
		Skip results

		Parameters
			integer $number : number of results to skip

		Return
			Olive\Pdo\Table
	*/
	final public function skip($number){
		$this->_verifySearchRequest();
		$this->skip_request=(int)$number;
		return $this;
	}

	/*
		Filter results

		Parameters
			string $field
			callable $callback

		Return
			Olive\Pdo\Table
	*/
	final public function filter($field,$callback){
		$this->_verifySearchRequest();
		if(!is_callable($callback)){
			throw new Exception("The filter's callback parameter must be callable");
		}
		$this->filters[(string)$field]=$callback;
		return $this;
	}

	/*
		Validate results

		Parameters
			string $field
			callable $callback

		Return
			Olive\Pdo\Table
	*/
	final public function validate($field,$callback){
		$this->_verifySearchRequest();
		if(!is_callable($callback)){
			throw new Exception("The validator's callback parameter must be callable");
		}
		$this->validators[(string)$field]=$callback;
		return $this;
	}

	/*
		Insert an element

		Parameters
			mixed $value: value for that element

		Return
			mixed
	*/
	abstract public function insert($value);

	/*
		Update an element

		Parameters
			mixed $value: value

		Return
			integer
	*/
	abstract public function update($value);

	/*
		Save an element

		Parameters
			mixed $value: value of that element

		Return
			mixed
	*/
	abstract public function save($value);

	/*
		Remove an element

		Return
			integer
	*/
	abstract public function remove();

	/*
		Return the number of elements for that query

		Return
			integer
	*/
	abstract public function count();

	/*
		Find directly rows

		Parameters
			string $field   : field name
			string $operator: operator
			mixed $value    : value

		Parameters
			array           : query list

		Return
			array
	*/
	final public function find($field=null,$operator=null,$value=null){
		$this->query=array();
		return $this->search($field,$operator,$value)->fetch();
	}

	/*
		Find directly one row

		Parameters
			string $field   : field name
			string $operator: operator
			mixed $value    : value

		Parameters
			array           : query list

		Return
			array
	*/
	final public function findOne($field=null,$operator=null,$value=null){
		$this->query=array();
		return $this->search($field,$operator,$value)->fetchOne();
	}

	/*
		Find the first value on the first row

		Parameters
			string $field   : field name
			string $operator: operator
			mixed $value    : value

		Parameters
			array           : query list

		Return
			array
	*/
	final public function findFirst($field=null,$operator=null,$value=null){
		$this->query=array();
		return $this->search($field,$operator,$value)->fetchFirst();
	}

	/*
		Fetch all results

		Return
			array
	*/
	abstract public function fetch();

	/*
		Fetch first result

		Return
			array
	*/
	abstract public function fetchOne();

	/*
		Fetch first field of the first result

		Return
			mixed
	*/
	abstract public function fetchFirst();
	
	/*
		Fetch results (with cache)
		
		Parameters
			string $key
			integer $lifetime

		Return
			array
	*/
	final public function fetchAndCache($key,$lifetime){
		$container=$this;
		return $this->cache($key,$lifetime,function() use($container){
			return $container->fetch();
		});
	}
	
	/*
		Fetch first result (with cache)
		
		Parameters
			string $key
			integer $lifetime

		Return
			array
	*/
	final public function fetchOneAndCache($key,$lifetime){
		$container=$this;
		return $this->cache($key,$lifetime,function() use($container){
			return $container->fetchOne();
		});
	}
	
	/*
		Fetch first field of first result (with cache)
		
		Parameters
			string $key
			integer $lifetime

		Return
			mixed
	*/
	final public function fetchFirstAndCache($key,$lifetime){
		$container=$this;
		return $this->cache($key,$lifetime,function() use($container){
			return $container->fetchFirst();
		});
	}

	/*
		Get the current value in the iteration

		Return
			array
	*/
	public function current(){
		return current($this->results);
	}

	/*
		Get the current key in the iteration

		Return
			integer
	*/
	public function key(){
		return key($this->results);
	}

	/*
		Iterate
	*/
	public function next(){
		next($this->results);
	}

	/*
		Rewind the iteration
	*/
	public function rewind(){
		reset($this->results);
	}

	/*
		Verify if the current position is valid

		Return
			boolean
	*/
	public function valid(){
		return key($this->results)!==null;
	}

}
