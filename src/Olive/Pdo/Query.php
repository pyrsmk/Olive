<?php

namespace Olive\Pdo;

use Olive\Pdo as Database;
use Olive\AbstractQuery;
use Olive\Exception;

/*
	PDO query
*/
class Query extends AbstractQuery{

	/*
		PDOStatement $cursor    : result cursor
		boolean $next           : true if the next rowset is valid
	*/
	protected $cursor;
	protected $next;

	/*
		Constructor

		Parameters
			Olive\AbstractDatabase $database
			mixed $name
	*/
	public function __construct(Database $database,$name){
		parent::__construct($database, $name);
		$this->query['from'] = array();
	}

	/*
		Update rows

		Parameters
			array $values   : values to insert
			array           : driver options
	*/
	public function update(array $data){
		try{
			// Get options
			$options=func_num_args()>1?
					 (array)func_get_arg(1):
					 array();
			// Prepare markers
			$set_values=array();
			$markers=array();
			foreach($data as $name=>$value){
				$marker=$this->database->getMarker();
				$set_values[$marker]=$value;
				$markers[]=$this->database->escape($name).'=:'.$marker;
			}
			// Prepare query
			$q=$this->_prepareQuery();
			$query='UPDATE '.$this->database->escape($this->database->getNamespace().$this->name).
				   ' SET '.implode(',',$markers).' '.$q['clauses']['join'].' '.$q['clauses']['where'];
			$data=array_merge($set_values,$q['values']);
			// Update rows
			$statement=$this->database->getDriver()->prepare($query,$options);
			$statement->execute($data);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	/*
		Remove a row

		Parameters
			array: driver options
	*/
	public function remove(){
		try{
			// Get options
			$options=func_num_args()>0?
					 (array)func_get_arg(0):
					 array();
			// Prepare request
			$q=$this->_prepareQuery();
			$query = 'DELETE FROM '.$this->database->escape($this->database->getNamespace().$this->name).' '.
					$q['clauses']['join'].' '.$q['clauses']['where'];
			// Remove row
			$statement=$this->database->getDriver()->prepare($query,$options);
			$statement->execute($q['values']);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}
	
	/*
		Add table alias
		
		Parameters
			string $table
			string $alias
		
		Return
			Olive\Pdo\Query
	*/
	public function from($table, $alias) {
		$this->query['from'][(string)$alias] = (string)$table;
		return $this;
	}

	/*
		Fetch all results

		Parameters
			array $options : driver options

		Return
			array
	*/
	public function fetch(){
		// Get options
		$options=func_num_args()>0?
				 (array)func_get_arg(0):
				 array();
		// Init cursor
		$this->_initCursor($options);
		// Get data
		try{
			$results = $this->cursor->fetchAll(\PDO::FETCH_ASSOC);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
		return $results;
	}

	/*
		Fetch first result

		Parameters
			array $options : driver options

		Return
			array
	*/
	public function fetchOne(){
		// Get options
		$options=func_num_args()>0?
				 (array)func_get_arg(0):
				 array();
		// Fetch results
		$results=$this->fetch($options);
		if($results){
			return $results[0];
		}
		else{
			return array();
		}
	}

	/*
		Fetch first field of the first result

		Parameters
			array $options : driver options

		Return
			mixed
	*/
	public function fetchFirst(){
		// Get options
		$options=func_num_args()>0?
				 (array)func_get_arg(0):
				 array();
		// Fetch result
		$result=$this->fetchOne($options);
		if(count($result)){
			return current($result);
		}
		else{
			return null;
		}
	}

	/*
		Return the number of elements for that query

		Return
			integer
	*/
	public function count() {
		$this->select('COUNT(*)');
		return $this->fetchFirst();
	}

	/*
		Initialize cursor

		Parameters
			array $options: driver options
	*/
	protected function _initCursor($options=array()){
		try{
			// Force closing previous cursor
			if($this->cursor){
				$this->cursor->closeCursor();
			}
			// Prepare request
			$q=$this->_prepareQuery();
			$query=$q['clauses']['select'].' '.
				   $q['clauses']['from'].' '.
				   $q['clauses']['join'].' '.
				   $q['clauses']['where'].' '.
				   $q['clauses']['orderby'].' '.
				   $q['clauses']['limit'];
			// Execute request
			$cursor=$this->database->getDriver()->prepare($query,$options);
			$cursor->execute($q['values']);
			$this->cursor=$cursor;
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	/*
		Prepare SQL clauses

		Return
			array
	*/
	protected function _prepareQuery(){
		// Prepare
		$clauses=array(
			'select' => '',
			'from' => '',
			'join' => '',
			'where' => '',
			'limit' => '',
			'orderby' => ''
		);
		$values=array();
		$namespace=$this->database->getNamespace();
		// Generate SELECT clause
		$selects=array();
		if($this->query['select']){
			foreach($this->query['select'] as $select){
				$s=$this->database->escape($select['field']);
				if($select['alias']){
					$s.=' AS '.$this->database->escape($select['alias']);
				}
				$selects[]=$s;
			}
		}
		if(!$selects){
			$selects[]='*';
		}
		$clauses['select']='SELECT '.implode(',',$selects);
		// Generate FORM clause
		$clauses['from'] = '';
		if(count($this->query['from'])) {
			$clauses['from'] = 'FROM '.implode(', ', array_map(
				function ($table, $alias) use($namespace) {
					return sprintf(
						"%s AS %s",
						$this->database->escape($namespace.$table),
						$this->database->escape($namespace.$alias)
					);
				},
				$this->query['from'],
				array_keys($this->query['from'])
			));
		}
		if(!in_array($this->name, $this->query['from'])) {
			$clauses['from'] .= $clauses['from'] ? ', ' : 'FROM ';
			$clauses['from'] .= $this->database->escape($namespace.$this->name);
		}
		// Generate JOIN clauses
		if($this->query['join']){
			$joins=array();
			$joined=array($namespace.$this->name);
			foreach($this->query['join'] as $join){
				// Prepare join variables
				if(in_array($join['container1'],$joined)){
					$field1 = $join['field1'];
					$table1 = $join['container1'];
					$table2 = $join['container2'];
					$field2 = $join['field2'];
				}
				else{
					$field1 = $join['field2'];
					$table1 = $join['container2'];
					$table2 = $join['container1'];
					$field2 = $join['field1'];
				}
				$joined[]=$table2;
				// Generate clause
				$joins[] = 'LEFT JOIN '.$this->database->escape($namespace.$table1).' ON '.
						 $this->database->escape($namespace.$table1).'.'.$this->database->escape($field1).'='.
						 $this->database->escape($namespace.$table2).'.'.$this->database->escape($field2);
			}
			$clauses['join']=implode(' ',$joins);
		}
		// Generate WHERE clause
		if($this->query['search']){
			$where=$this->_prepareWhere($this->query['search']);
			$clauses['where']='WHERE ('.$where['query'].')';
			$values=$where['values'];
		}
		// Generate ORDER BY clause
		if($this->query['sort']){
			$orderby=array();
			foreach($this->query['sort'] as $sort){
				$orderby[]=$this->database->escape($sort['field']).' '.
						   ($sort['order']=='asc'?'ASC':'DESC');
			}
			$clauses['orderby']='ORDER BY '.implode(',',$orderby);
		}
		// Generate LIMIT clause
		if($this->query['limit'] || $this->query['skip']){
			if($this->query['limit']){
				$limit=$this->query['skip'].','.$this->query['limit'];
			}
			else{
				$limit=$this->query['skip'].',9999999999';
			}
			$clauses['limit']='LIMIT '.$limit;
		}
		// Return the prepared query
		return array('clauses'=>$clauses,'values'=>$values);
	}

	/*
		Prepare searches

		Parameters
			array $searches

		Return
			array
	*/
	protected function _prepareWhere(array $searches){
		$ands=array();
		$values=array();
		// Browse AND searches
		foreach($searches as $or_searches){
			$ors=array();
			// Browse OR searches
			foreach($or_searches as $search){
				// Translate operator
				switch($search['operator']){
					case 'is':      	$operator='='; break;
					case 'is not':  	$operator='<>'; break;
					case 'greater': 	$operator='>'; break;
					case 'less':    	$operator='<'; break;
					case 'in':      	$operator=' IN '; break;
					case 'not in':  	$operator=' NOT IN '; break;
					case 'like':		$operator=' LIKE '; break;
					case 'not like':	$operator=' NOT LIKE '; break;
					case 'match':		$operator=' REGEXP '; break;
					case 'not match':	$operator=' NOT REGEXP '; break;
				}
				// Prepare IN/NOT IN value
				if($search['operator']=='in' || $search['operator']=='not in'){
					foreach($search['value'] as $v){
						$marker = $this->database->getMarker();
						$values[':'.$marker] = $v;
					}
					$value = '('.implode(',', array_keys($values)).')';
				}
				// Prepare basic value
				else{
					$marker = $this->database->getMarker();
					$value = ':'.$marker;
					$values[$value] = $search['value'];
				}
				// Add OR clause
				$ors[] = $this->database->escape($search['field']).$operator.$value;
			}
			// Add AND clause
			$ands[]='('.implode(') OR (', $ors).')';
		}
		return array(
			'query' => '('.implode(') AND (', $ands).')',
			'values' => $values
		);
	}

}