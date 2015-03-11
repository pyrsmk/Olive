<?php

namespace Olive\Pdo;

use Olive\Exception;
use Olive\Exception\DatabaseError;
use Olive\Container;

/*
	Table data container

	Author
		Aurélien Delogu (dev@dreamysource.fr)
*/
class Table extends Container{

	/*
		PDOStatement $cursor    : result cursor
		boolean $next           : true if the next rowset is valid
		integer $marker         : a marker id
		array $join_request     : join request
		array $group_request    : group request
		array $having_request   : having request
	*/
	protected $cursor;
	protected $next;
	protected $marker=0;
	protected $join_request=array();
	protected $group_request=array();
	protected $having_request=array();

	/*
		Insert a row

		Parameters
			array $values   : values to insert
			array           : driver options

		Return
			Olive\Pdo\Table

		Throw
			Olive\Exception\DatabaseError
	*/
	public function insert($values){
		try{
			// Format
			$values=(array)$values;
			if(func_num_args()>1){
				$options=(array)func_get_arg(1);
			}
			else{
				$options=array();
			}
			// Prepare markers
			$names=array();
			$markers=array();
			foreach($values as $name=>$value){
				$names[]=$this->_escape($name);
				$markers[]=':'.$name;
			}
			// Prepare query
			$query='INSERT INTO '.$this->_escape($this->name).' ('.implode(',',$names).') VALUES ('.implode(',',$markers).')';
			// Log
			/*$this->_log(array(
				'query'     => $query,
				'values'    => $values,
				'options'   => $options
			));*/
			// Insert new row
			$this->olive->getEngine()->prepare($query,$options)->execute($values);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$this->_closeCursor();
		return $this->olive->getEngine()->lastInsertId();
	}

	/*
		Update rows

		Parameters
			array $values   : values to insert
			array           : driver options

		Return
			Olive\Pdo\Table

		Throw
			Olive\Exception\DatabaseError
	*/
	public function update($values){
		$this->_verifySearchRequest();
		try{
			// Format values
			$values=(array)$values;
			if(func_num_args()>1){
				$options=(array)func_get_arg(1);
			}
			else{
				$options=array();
			}
			// Prepare markers
			$set_values=array();
			$markers=array();
			foreach($values as $name=>$value){
				$marker=$this->_getMarker();
				$set_values[$marker]=$value;
				$markers[]=$this->_escape($name).'=:'.$marker;
			}
			// Prepare query
			$request=$this->_prepareQuery();
			$query='UPDATE '.$this->_escape($this->name).
				   ' SET '.implode(',',$markers).' '.$request['query']['join'].' '.$request['query']['where'];
			$values=array_merge($set_values,$request['values']);
			// Log
			/*$this->_log(array(
				'query'     => $query,
				'values'    => $values,
				'options'   => $options
			));*/
			// Update rows
			$statement=$this->olive->getEngine()->prepare($query,$options);
			$statement->execute(values);
			$rows=$statement->rowCount();
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$this->_closeCursor();
		return $rows;
	}

	/*
		Save a row

		Parameters
			array $values   : values
			array           : driver options

		Return
			Olive\Pdo\Table

		Throw
			Olive\Exception : if there's an invalid operator (other than 'is') in a search()
			Olive\Exception\DatabaseError
	*/
	public function save($values=array()){
		$this->_verifySearchRequest();
		try{
			// Format
			if(func_num_args()>1){
				$options=(array)func_get_arg(1);
			}
			else{
				$options=array();
			}
			// Prepare data
			$insert_names=array();
			$insert_markers=array();
			$update_markers=array();
			$execute_values=array();
			foreach((array)$this->search_request as $value){
				if(strtolower($value[1])!='is'){
					throw new Exception("Forbidden use of operators other than 'is' with save operation");
				}
				$insert_names[]=$this->_escape($value[0]);
				$marker=$this->_getMarker();
				$insert_markers[]=':'.$marker;
				$execute_values[$marker]=$value[2];
			}
			foreach((array)$values as $name=>$value){
				$insert_names[]=$this->_escape($name);
				$marker=$this->_getMarker();
				$insert_markers[]=':'.$marker;
				$execute_values[$marker]=$value;
				$marker=$this->_getMarker();
				$update_markers[]=$name.'=:'.$marker;
				$execute_values[$marker]=$value;
			}
			// Prepare query
			$request=$this->_prepareQuery();
			$query='INSERT INTO '.$this->_escape($this->name).
				   ' ('.implode(',',$insert_names).
				   ') VALUES ('.implode(',',$insert_markers).
				   ') ON DUPLICATE KEY UPDATE '.implode(',',$update_markers);
			// Log
			/*$this->_log(array(
				'query'     => $query,
				'values'    => $execute_values,
				'options'   => $options
			));*/
			// Save row
			$statement=$this->olive->getEngine()->prepare($query,$options);
			$statement->execute($execute_values);
			$rows=$statement->rowCount();
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$this->_closeCursor();
		$id=$this->olive->getEngine()->lastInsertId();
		if($id){
			return $id;
		}
		else{
			return $rows;
		}
	}

	/*
		Remove a row

		Parameters
			array: driver options

		Return
			Olive\Pdo\Table

		Throw
			Olive\Exception\DatabaseError
	*/
	public function remove(){
		$this->_verifySearchRequest();
		try{
			// Format options
			if(func_num_args()){
				$options=(array)func_get_arg(0);
			}
			else{
				$options=array();
			}
			// Prepare request
			$request=$this->_prepareQuery();
			$query='DELETE FROM '.$this->_escape($this->name).' '.$request['query']['join'].' '.$request['query']['where'];
			// Log
			/*$this->_log(array(
				'query'     => $query->queryString,
				'values'    => $request['values'],
				'options'   => $options
			));*/
			// Remove row
			$statement=$this->olive->getEngine()->prepare($query,$options);
			$statement->execute($request['values']);
			$rows=$statement->rowCount();
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$this->_closeCursor();
		return $rows;
	}

	/*
		Fetch all results

		Parameters
			array: driver options

		Return
			array

		Throw
			Olive\Exception\DatabaseError
	*/
	public function fetch(){
		$this->_verifySearchRequest();
		// Force closing cursor
		if($this->cursor){
			$this->_closeCursor();
		}
		// Format options
		if(func_num_args()){
			$options=(array)func_get_arg(0);
		}
		else{
			$options=array();
		}
		// Init cursor
		$this->_initCursor($options);
		// Get data
		try{
			$results=$this->cursor->fetchAll();
			foreach($results as $i=>&$result){
				// Filtering
				foreach($this->filters as $field=>$callback){
					if(isset($result[$field])){
						$result[$field]=$callback($result[$field]);
					}
				}
				// Validating
				foreach($this->validators as $field=>$callback){
					if(isset($result[$field])){
						if(!$callback($result[$field])){
							unset($result);
						}
					}
				}
			}
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$this->_closeCursor();
		return $results;
	}

	/*
		Fetch first result

		Parameters
			array: driver options

		Return
			array

		Throw
			Olive\Exception\DatabaseError
	*/
	public function fetchOne(){
		$results=$this->fetch();
		if($results){
			// Filtering
			foreach($this->filters as $field=>$callback){
				if(isset($results[0][$field])){
					$results[0][$field]=$callback($results[0][$field]);
				}
			}
			// Validating
			foreach($this->validators as $field=>$callback){
				if(isset($results[0][$field])){
					if(!$callback($results[0][$field])){
						return array();
					}
				}
			}
			return $results[0];
		}
		else{
			return array();
		}
	}

	/*
		Fetch first field of the first result

		Return
			mixed
	*/
	public function fetchFirst(){
		$result=$this->fetchOne();
		if(count($result)){
			return $result[0];
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
	public function count(){
		$this->select_request=array('COUNT(*)');
		return $this->fetchFirst();
	}

	/*
		Join two tables

		Parameters
			string $field1: first field to join
			string $field2: second field to join

		Return
			Olive\Pdo\Table
	*/
	public function join($field1,$field2){
		$this->_verifySearchRequest();
		$this->join_request[]=array($field1,$field2);
		return $this;
	}

	/*
		Group data

		Parameters
			string $field: the field to group on

		Return
			Olive\Pdo\Table
	*/
	public function group($field){
		$this->_verifySearchRequest();
		$this->group_request[]=$field;
		return $this;
	}

	/*
		Having clause

		Parameters
			string $field   : field name
			string $operator: operator
			mixed $value    : value

		Return
			Olive\Pdo\Table

		Throw
			Olive\Exception     : if the field parameter is not a string nor an array
			Olive\Exception     : if the operator parameter is not a string
	*/
	public function having($field,$operator,$value){
		// Verify parameters
		if(!is_string($field)){
			throw new Exception("The field parameter must be a string or an array");
		}
		elseif(!is_string($operator)){
			throw new Exception("The operator parameter must be a string");
		}
		// Register
		$this->having_request[]=array($field,$operator,$value);
		return $this;
	}

	/*
		Initialize cursor

		Parameters
			array $options: driver options

		Throw
			Olive\Exception\DatabaseError
	*/
	protected function _initCursor($options=array()){
		try{
			// Prepare request
			$request=$this->_prepareQuery();
			$query=$request['query']['select'].' '.
				   $request['query']['from'].' '.
				   $request['query']['join'].' '.
				   $request['query']['where'].' '.
				   $request['query']['limit'].' '.
				   $request['query']['orderby'].' '.
				   $request['query']['groupby'].' '.
				   $request['query']['having'];
			// Log
			/*$this->_log(array(
				'query'   => $query,
				'values'  => $request['values'],
				'options' => $options
			));*/
			// Execute request
			$cursor=$this->olive->getEngine()->prepare($query,$options);
			$cursor->execute($request['values']);
			$this->cursor=$cursor;
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
	}

	/*
		Close opened cursor

		Throw
			Olive\Exception\DatabaseError
	*/
	protected function _closeCursor(){
		if($this->cursor){
			try{
				$this->cursor->closeCursor();
				$this->cursor=null;
			}
			catch(\Exception $e){
				throw new DatabaseError($e->getMessage());
			}
		}
	}

	/*
		Prepare SQL clause

		Return
			array

		Throw
			Olive\Exception: if a sort parameter is invalid
	*/
	protected function _prepareQuery(){
		$prepared=array();
		$values=array();
		$namespace=$this->olive->getNamespace();
		// ===== Generate SELECT clause =====
		if($select=$this->select_request){
			foreach($select as $alias=>&$field){
				if(is_string($alias)){
					$selects[]=$this->_escape($field).' AS '.$this->_escape($alias);
				}
				else{
					$selects[]=$this->_escape($field);
				}
			}
		}
		if(!$selects){
			$selects=array('*');
		}
		$prepared['select']='SELECT '.implode(',',$selects);
		// ===== Generate FORM clause =====
		$prepared['from']='FROM '.$this->_escape($this->name);
		// ===== Generate JOIN clauses =====
		if($this->join_request){
			$joins=array();
			$joined[]=$this->name;
			foreach((array)$this->join_request as $join){
				list($table1,)=explode('.',$join[0]);
				list($table2,)=explode('.',$join[1]);
				if(in_array($table1,$joined)){
					$joins[]='LEFT JOIN '.$this->_escape($namespace.$table2).' ON '.$this->_escape($namespace.$join[0]).'='.$this->_escape($namespace.$join[1]);
					$joined[]=$table2;
				}
				else{
					$joins[]='LEFT JOIN '.$this->_escape($namespace.$table1).' ON '.$this->_escape($namespace.$join[1]).'='.$this->_escape($namespace.$join[0]);
					$joined[]=$table1;
				}
			}
			$prepared['join']=implode(' ',$joins);
		}
		// ===== Generate WHERE clause =====
		if($searches=$this->search_request){
			list($where_query,$where_values)=$this->_prepareSearch($searches);
			$prepared['where']='WHERE ('.$where_query.')';
			$values=array_merge($values,$where_values);
		}
		// ===== Generate ORDER BY clause =====
		if($sorts=$this->sort_request){
			$orderby=array();
			foreach($sorts as $field=>$order){
				$order=strtoupper($order);
				if($order!='ASC' && $order !='DESC'){
					throw new Exception("Invalid '$order' sort parameter");
				}
				$orderby[]=$this->_escape($field).' '.$order;
			}
			$prepared['orderby']='ORDER BY '.implode(',',$orderby);
		}
		// ===== Generate LIMIT clause =====
		if(is_int($limit=$this->limit_request) || $skip=$this->skip_request){
			if($limit){
				$limit=(int)$skip.','.$limit;
			}
			else{
				$limit=$skip.',999999999999999';
			}
			$prepared['limit']='LIMIT '.$limit;
		}
		// ===== Generate GROUP BY clause =====
		if($groups=$this->group_request){
			foreach($groups as &$field){
				$field=$this->_escape($field);
			}
			$prepared['groupby']='GROUP BY '.implode(',',$groups);
		}
		// ===== Generate HAVING clause =====
		if($havings=$this->having_request){
			list($having_query,$having_values)=$this->_prepareSearch($havings);
			$prepared['having']='HAVING ('.$having_query.')';
			$values=array_merge($values,$having_values);
		}
		// Return prepared query
		return array('query'=>$prepared,'values'=>$values);
	}

	/*
		Prepare searches

		Parameters
			array $searches

		Return
			array

		Throw
			Olive\Exception: if a search operator is invalid
			Olive\Exception: if a search parameter is invalid
	*/
	protected function _prepareSearch(array $searches){
		$ands=array();
		$values=array();
		foreach($searches as $search){
			$query='';
			// Translate operator
			switch(strtolower($search[1])){
				case 'is':      $operator='='; break;
				case 'is not':  $operator='<>'; break;
				case 'greater': $operator='>'; break;
				case 'less':    $operator='<'; break;
				case 'in':      $operator=' IN '; break;
				case 'not in':  $operator=' NOT IN '; break;
				default:        throw new Exception("Invalid '{$search[1]}' operator");
			}
			// Prepare IN value
			if(is_array($value=$search[2])){
				$markers=array();
				foreach($value as $val){
					$marker=$this->_getMarker();
					$markers[]=':'.$marker;
					$values[$marker]=$val;
				}
				$query.='('.implode(',',$markers).')';
			}
			// Prepare basic value
			else{
				$marker=$this->_getMarker();
				$query.=':'.$marker;
				$values[$marker]=$value;
			}
			// Single query
			if(is_string($search[0])){
				$ands[]=$this->_escape($search[0]).$operator.$query;
			}
			// OR queries
			elseif(is_array($search[0])){
				$ors=array();
				foreach($search as $sub){
					$ors[]=$this->_prepareSearch($sub);
				}
				$ands[]='('.implode(') OR (',$ors).')';
			}
			else{
				throw new Exception("Invalid search() parameter");
			}
		}
		return array(
			'('.implode(') AND (',$ands).')',
			$values
		);
	}

	/*
		Generate a new marker

		Return
			string
	*/
	protected function _getMarker(){
		return 'marker'.(++$this->marker);
	}

	/*
		Rewind the iteration

		Throw
			Olive\Exception\DatabaseError
	*/
	public function rewind(){
		// Verify
		if(!$this->query['search']){
			throw new Exception("search() was not called");
		}
		// Init cursor
		$this->_initCursor();
		try{
			// Retrieve data
			$this->results=$this->cursor->fetchAll();
			// Next rowset
			try{
				$this->next=$this->cursor->nextRowset();
			}
			catch(\Exception $e){}
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
	}

	/*
		Verify if the current position is valid

		Return
			boolean

		Throw
			Olive\Exception\DatabaseError
	*/
	public function valid(){
		// End of iteration
		if($valid=(key($this->results)===null) && !$this->next){
			$this->_closeCursor();
		}
		return !$valid;
	}

	/*
		Escape a string for an SQL request

		Parameters
			string $str

		Return
			string
	*/
	protected function _escape($str){
		// Don't escape jokers
		if($str!='*'){
			// Extract function
			if(preg_match('/([a-z_]+)\((.+?)\)/i',$str,$matches)){
				$function=$matches[1];
				$str=$matches[2];
			}
			// Escape tables and fields
			$pieces=explode('.',(string)$str);
			foreach($pieces as &$piece){
				if($piece!='*'){
					$piece='`'.$piece.'`';
				}
			}
			$str=implode('.',$pieces);
			// Add function
			if($function){
				$str="$function($str)";
			}
		}
		return $str;
	}

}
