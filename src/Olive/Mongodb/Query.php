<?php

namespace Olive\Mongodb;

use Olive\Mongodb as Database;
use Olive\AbstractQuery;
use Olive\Exception;

/*
	MongoDB query
*/
class Query extends AbstractQuery {

	/*
		MongoCursor $cursor : result cursor
	*/
	protected $cursor;

	/*
		Update a document

		Parameters
			array $document : document values
			array $options  : driver options
	*/
	public function update(array $document) {
		// Get options
		$options=func_num_args()>1?
				 (array)func_get_arg(1):
				 array();
		$options['limit']=0;
		if(array_key_exists('upsert',$options)){
			unset($options['upsert']);
		}
		// Update documents
		try{
			$bulk = new \MongoDB\Driver\BulkWrite;
			$bulk->update(
				$this->_prepareQuery($this->query['search']),
				array('$set'=>(array)$document),
				$options
			);
			$this->database->getDriver()->executeBulkWrite($this->name, $bulk);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	/*
		Remove an element

		Parameters
			array $options : driver options
	*/
	public function remove(){
		// Get options
		$options=func_num_args()>0?
				 (array)func_get_arg(0):
				 array();
		// Remove document
		try{
			$bulk = new \MongoDB\Driver\BulkWrite;
			$bulk->delete($this->_prepareQuery($this->query['search']), $options);
			$this->database->getDriver()->executeBulkWrite($this->name, $bulk);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	/*
		Fetch all elements

		Return
			array
	*/
	public function fetch(){
		// Get data
		$this->_initCursor();
		$results=$this->_formatResults(iterator_to_array($this->cursor));
		// Resolve aliases
		$results=$this->_resolveAliases($this->database->getNamespace().$this->name,$results);
		// Join collections
		$results=$this->_joinCollectionsTo($results);
		// Format IDs
		foreach($results as &$result){
			$result['_id']=(string)$result['_id'];
		}
		return $results;
	}

	/*
		Fetch first element

		Return
			array
	*/
	public function fetchOne(){
		// Get data
		$this->_initCursor();
		$this->cursor->rewind();
		$result=$this->_formatResults($this->cursor->current());
		// Resolve aliases
		$results=$this->_resolveAliases($this->database->getNamespace().$this->name,array($result));
		// Join collections
		$results=$this->_joinCollectionsTo($results);
		$result=$results[0];
		// Format ID
		if(count($result)) {
			$result['_id']=(string)$result['_id'];
		}
		return $result;
	}

	/*
		Fetch first field of the first result

		Return
			mixed
	*/
	public function fetchFirst(){
		// Fetch result
		if($result=$this->fetchOne()){
			$field=current($result);
		}
		return $field;
	}

	/*
		Return the number of elements for that query

		Return
			integer
	*/
	public function count(){
		// Get data
		$this->_initCursor();
		return count(iterator_to_array($this->cursor));
	}

	/*
		Initialize the cursor
	*/
	protected function _initCursor(){
		try{
			// Prepare projection
			$projection=array();
			foreach($this->query['select'] as $select){
				if(strpos($select['field'],'.')===false){
					$projection[$select['field']]=1;
				}
			}
			// Prepare sorting
			$sorts=array();
			foreach($this->query['sort'] as $sort){
				if($sort['order']=='asc'){
					$order=1;
				}
				else{
					$order=-1;
				}
				$sorts[$sort['field']]=$order;
			}
			// Execute query
			$query = new \MongoDB\Driver\Query($this->_prepareQuery($this->query['search']), array(
				'projection' => $projection,
				'sort' => $sorts,
				'limit' => $this->query['limit'],
				'skip' => $this->query['skip']
			));
			$cursor = $this->database->getDriver()->executeQuery($this->name, $query);
			// Save cursor
			$this->cursor=new \IteratorIterator($cursor);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
	}

	/*
		Prepare one query

		Parameters
			array $searches

		Return
			array
	*/
	protected function _prepareQuery(array $searches){
		// Compose one search condition
		$composeCondition=function($field,$operator,$value){
			switch($operator){
				case 'is':
					return array($field=>$value);
					break;
				case 'is not':
					return array($field=>array('$ne'=>$value));
					break;
				case 'greater':
					return array($field=>array('$gt'=>$value));
					break;
				case 'less':
					return array($field=>array('$lt'=>$value));
					break;
				case 'in':
					return array($field=>array('$in'=>$value));
					break;
				case 'not in':
					return array($field=>array('$nin'=>$value));
					break;
				case 'like':
					$value=str_replace(
						array('_','%'),
						array('.','.+?'),
						$value
					);
					return array($field=>array('$regex'=>$value));
					break;
				case 'not like':
					$value=str_replace(
						array('_','%'),
						array('.','.+?'),
						$value
					);
					return array($field=>array('$not'=>array('$regex'=>$value)));
					break;
				case 'match':
					return array($field=>array('$regex'=>$value));
					break;
				case 'not match':
					return array($field=>array('$not'=>array('$regex'=>$value)));
					break;
			}
		};
		// Generate queries
		$ands=array();
		foreach($searches as $or_searches){
			$ors=array();
			foreach($or_searches as $search){
				$value=$search['value'];
				// Format IDs
				if($search['field']=='_id'){
					if(is_array($value)){
						foreach($value as &$v){
							$v=new \MongoDB\BSON\ObjectID($v);
						}
					}
					else{
						$value=new \MongoDB\BSON\ObjectID($value);
					}
				}
				// Add OR query
				$ors[]=$composeCondition($search['field'],$search['operator'],$value);
			}
			// Add AND query
			if(count($ors)==1){
				$ands[]=$ors[0];
			}
			else{
				$ands[]=array('$or'=>$ors);
			}
		}
		// Return the full query
		switch(count($ands)) {
			case 0:
				return array();
				break;
			case 1:
				return $ands[0];
				break;
			default:
				return array('$and'=>$ands);
		}
	}

	/*
		Join several collections

		Parameters
			array $results

		Return
			array
	*/
	protected function _joinCollectionsTo($results){
		$joined=array($this->name);
		foreach($this->query['join'] as $join){
			// Prepare join variables
			if(in_array($join['container1'],$joined)){
				$field1=$join['field1'];
				$collection2=$join['container2'];
				$field2=$join['field2'];
			}
			else{
				$field1=$join['field2'];
				$collection2=$join['container1'];
				$field2=$join['field1'];
			}
			$joined[]=$collection2;
			// Prepare values
			$values=array();
			foreach($results as $result){
				if($result[$field1]!==null){
					$values[]=$result[$field1];
				}
			}
			// Prepare mapping : array(field1_value=>ids)
			$map=array();
			foreach($results as $id=>$result){
				if(isset($map[$field1])){
					$map[$result[$field1]]=array();
				}
				$map[$result[$field1]][]=$id;
			}
			// Get new results
			$new_results=$this->database->$collection
										->search($field,'in',$values)
										->fetch();
			$new_results=$this->_resolveAliases($collection2,$new_results);
			// Merge results
			foreach($new_results as $new_result){
				foreach($map[$new_result[$field2]] as $id){
					$results[$id]=array_merge($results[$id],$new_result);
				}
			}
		}
		return $results;
	}

	/*
		Resolve aliases in results

		Parameters
			string $collection
			array $results

		Return
			array
	*/
	protected function _resolveAliases($collection,$results){
		// Define resolve function
		$resolve=function($field,$alias,&$results){
			foreach($results as &$result){
				if(isset($result[$field])){
					if(isset($result[$alias])){
						throw new Exception("Ambiguous alias detected, ${$result[$alias]} is already set");
					}
					$result[$alias]=$result[$field];
					unset($result[$field]);
				}
			}
		};
		// Browse aliases
		foreach($this->query['select'] as $select){
			if(!$select['alias']){
				continue;
			}
			// Inner collection
			if(strpos($select['field'],'.')===false){
				if($collection==$this->database->getNamespace().$this->name){
					$resolve($select['field'],$select['alias'],$results);
				}
			}
			// Outer collections
			else{
				list($coll,$field)=explode('.',$select['field']);
				if($coll==$collection){
					$resolve($field,$select['alias'],$results);
				}
			}
		}
		return $results;
	}
	
	/*
		Format results
		
		Parameters
			mixed $results
		
		Return
			mixed
	*/
	protected function _formatResults($results) {
		if(is_array($results)) {
			foreach($results as &$result) {
				if(is_array($result) || (is_object($result) && $result instanceof \stdClass)) {
					$result = $this->_formatResults((array)$result);
				}
			}
		}
		else if(is_object($results) && $results instanceof \stdClass) {
			$results = $this->_formatResults((array)$results);
		}
		return $results;
	}

}