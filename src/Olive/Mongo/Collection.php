<?php

namespace Olive\Mongo;

use Olive\Exception;
use Olive\Exception\DatabaseError;
use Olive\Container;
use MongoCollection;
use MongoId;

/*
	Collection data container

	Author
		Aurélien Delogu (dev@dreamysource.fr)
*/
class Collection extends Container{

	/*
		boolean $write_concern
		boolean $auto_format_ids
	*/
	protected $write_concern;
	protected $auto_format_ids;

	/*
		MongoCollection $collection : collection
		MongoCursor $cursor         : result cursor
		array $ids                  : MongoID cache list
	*/
	protected $collection;
	protected $cursor;
	protected $ids=array();

	/*
		Initialize container
	*/
	protected function _init(){
		$collection=$this->name;
		$this->collection=$this->olive->getEngine()->$collection;
	}

	/*
		Set/get write concern policy

		Parameters
			boolean $flag

		Return
			boolean, Olive\Mongo\Collection
	*/
	public function writeConcern($flag=null){
		if($flag===null){
			if($this->write_concern!==null){
				return $this->write_concern;
			}
			else{
				return $this->olive->writeConcern();
			}
		}
		else{
			$this->write_concern=(bool)$flag;
			return $this;
		}
	}

	/*
		Set/get auto format IDs

		Parameters
			boolean $flag

		Return
			boolean, Olive\Mongo\Collection
	*/
	public function autoFormatIds($flag=null){
		if($flag===null){
			if($this->auto_format_ids!==null){
				return $this->auto_format_ids;
			}
			else{
				return $this->olive->autoFormatIds();
			}
		}
		else{
			$this->auto_format_ids=(bool)$flag;
			return $this;
		}
	}

	/*
		Format an ID with MongoID object

		Parameters
			string $id

		Return
			MongoId
	*/
	protected function _formatMongoId($id){
		if(!$this->autoFormatIds()){
			return $id;
		}
		if(!($mid=&$this->ids[$id])){
			$mid=new MongoId($id);
		}
		return $mid;
	}

	/*
		Create an index

		Parameters
			string $field   : field to create an index on
			string $sort    : sorting for that index (default: ascendant)
	*/
	public function index($field,$sort='asc'){
		// Format
		$field=(string)$field;
		if($sort=='asc'){
			$sort=1;
		}
		else{
			$sort=-1;
		}
		// Ensure index
		$this->collection->ensureIndex(array($field=>$sort));
		// Close cursor
		$this->cursor=null;
		return $this;
	}

	/*
		Insert a document

		Parameters
			array $document : document values
			array           : driver options

		Return
			Olive\Mongo\Collection

		Throw
			Olive\Exception : if an unsupported type of document was specified
			Olive\Exception\DatabaseError
	*/
	public function insert($document){
		// Format
		if(func_num_args()>1){
			$options=(array)func_get_arg(1);
			if(!array_key_exists('w',$options)){
				$options['w']=$this->writeConcern();
			}
		}
		else{
			$options=array('w'=>$this->writeConcern());
		}
		// Log
		/*$this->_log(array(
			'collection'=> $this->name,
			'method'    => 'insert',
			'document'  => $document,
			'options'   => $options
		));*/
		// Insert the new document
		try{
			$this->collection->insert($document=(array)$document,$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		// Close cursor
		$this->cursor=null;
		return (string)$document['_id'];
	}

	/*
		Update a document

		Parameters
			array $document : document values
			array $options  : driver options

		Return
			Olive\Mongo\Collection

		Throw
			Olive\Exception : if find() was not called
			Olive\Exception : if an unsupported type of document was specified
			Olive\Exception\DatabaseError
	*/
	public function update($document){
		$this->_verifySearchRequest();
		// Init options
		if(func_num_args()>1){
			$options=(array)func_get_arg(1);
			if(!array_key_exists('w',$options)){
				$options['w']=$this->writeConcern();
			}
			if(!array_key_exists('multiple',$options)){
				$options['multiple']=true;
			}
			unset($options['upsert']);
		}
		else{
			$options=array(
				'w'         => $this->writeConcern(),
				'multiple'  => true
			);
		}
		// Log
		/*$this->_log(array(
			'collection'=> $this->name,
			'method'    => 'update',
			'search'    => $search,
			'document'  => $document,
			'options'   => $options
		));*/
		// Update documents
		try{
			$r=$this->collection->update($this->_prepareQuery($this->search_request),array('$set'=>(array)$document),$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		// Close cursor
		$this->cursor=null;
		return $r['n'];
	}

	/*
		Insert or update an element

		Parameters
			mixed $document : document to insert or update
			array $options  : driver options

		Return
			Olive\Mongo\Collection

		Throw
			Olive\Exception : if an unsupported type of document was specified
			Olive\Exception\DatabaseError
	*/
	public function save($document){
		// Format options
		if(func_num_args()>1){
			$options=(array)func_get_arg(1);
			if(!array_key_exists('w',$options)){
				$options['w']=$this->writeConcern();
			}
		}
		else{
			$options=array('w'=>$this->writeConcern());
		}
		// Log
		/*$this->_log(array(
			'collection'=> $this->name,
			'method'    => 'remove',
			'document'  => $document,
			'options'   => $options
		));*/
		// Save document
		try{
			$r=$this->collection->save($document,$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		// Close cursor
		$this->cursor=null;
		return $document['_id'];
	}

	/*
		Remove an element

		Parameters
			array           : driver options

		Return
			Olive\Mongo\Collection

		Throw
			Olive\Exception : if find() was not called
			Olive\Exception\DatabaseError
	*/
	public function remove(){
		$this->_verifySearchRequest();
		// Format
		if(func_num_args()>1){
			$options=(array)func_get_arg(1);
			if(!array_key_exists('w',$options)){
				$options['w']=$this->writeConcern();
			}
		}
		else{
			$options=array('w'=>$this->writeConcern());
		}
		// Log
		/*$this->_log(array(
			'collection'=> $this->name,
			'method'    => 'remove',
			'query'     => $search['query'],
			'fields'    => $search['fields'],
			'options'   => $options
		));*/
		// Remove document
		try{
			$r=$this->collection->remove($this->_prepareQuery($this->search_request),$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		// Close cursor
		$this->cursor=null;
		return $r['n'];
	}

	/*
		Fetch all elements

		Return
			array
	*/
	public function fetch(){
		$this->_verifySearchRequest();
		// Force closing cursor
		$this->cursor=null;
		// Get data
		$this->_initCursor();
		$results=iterator_to_array($this->cursor);
		foreach($results as &$result){
			// Format IDs
			if($result['_id']){
				if($this->select_request && !in_array('_id',$this->select_request)){
					unset($result['_id']);
				}
				else{
					$result['_id']=(string)$result['_id'];
				}
			}
			// Set aliases
			foreach((array)$this->select_request as $new=>$old){
				if(is_string($new)){
					$result[$new]=$result[$old];
					unset($result[$old]);
				}
			}
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
		// Close cursor
		$this->cursor=null;
		return $results;
	}

	/*
		Fetch first element

		Return
			array
	*/
	public function fetchOne(){
		$this->_verifySearchRequest();
		// Force closing cursor
		$this->cursor=null;
		// Get data
		$this->_initCursor();
		$this->cursor->rewind();
		$result=$this->cursor->current();
		// Format ID
		if($result['_id']){
			if($this->select_request && !in_array('_id',$this->select_request)){
				unset($result['_id']);
			}
			else{
				$result['_id']=(string)$result['_id'];
			}
		}
		// Set aliases
		foreach((array)$this->select_request as $alias=>$field){
			if(is_string($alias)){
				$result[$alias]=$result[$field];
				unset($result[$field]);
			}
		}
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
		// Close cursor
		$this->cursor=null;
		return $result;
	}

	/*
		Fetch first field of the first result

		Return
			mixed
	*/
	public function fetchFirst(){
		// Get result
		if($result=$this->fetchOne()){
			$field=current($result);
			// Format ID
			if($field instanceof MongoId){
				$field=(string)$field;
			}
		}
		return $field;
	}

	/*
		Return the number of elements for that query

		Return
			integer
	*/
	public function count(){
		$this->_verifySearchRequest();
		// Force closing cursor
		$this->cursor=null;
		// Get data
		$this->_initCursor();
		$count=$this->cursor->count(true);
		// Close cursor
		$this->cursor=null;
		return $count;
	}

	/*
		Initialize the cursor

		Throw
			Olive\Exception: if a sort parameter is invalid
			Olive\Exception\DatabaseError
	*/
	protected function _initCursor(){
		// Get query
		try{
			// Execute query
			$cursor=$this->collection->find($this->_prepareQuery($this->search_request)/*,(array)$this->select_request*/);
			// Finalize
			if($sort=$this->sort_request){
				foreach($sort as $field=>&$order){
					switch(strtolower($order)){
						case 'asc':
							$order=1;
							break;
						case 'desc':
							$order=-1;
							break;
						default:
							throw new Exception("Invalid '$order' sort parameter");
					}
				}
				$cursor->sort($sort);
			}
			if($limit=$this->limit_request){
				$cursor->limit($limit);
			}
			if($skip=$this->skip_request){
				$cursor->skip($skip);
			}
			// Log
			/*$this->_log(array(
				'collection'=> $this->name,
				'method'    => 'fetch',
				'find'      => $find,
				'sort'      => $sort,
				'limit'     => $limit,
				'skip'      => $skip
			));*/
			// Save cursor
			$this->cursor=$cursor;
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
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
			switch(strtolower($operator)){
				case 'is':      return array($field=>$value);
				case 'is not':  return array($field=>array('$ne'=>$value));
				case 'greater': return array($field=>array('$gt'=>$value));
				case 'less':    return array($field=>array('$lt'=>$value));
				case 'in':      return array($field=>array('$in'=>$value));
				case 'not in':  return array($field=>array('$nin'=>$value));
				default:        throw new Exception("Invalid '$operator' operator");
			}
		};
		// Generate query
		$query=array();
		foreach($searches as $search){
			// Format IDs
			if($search[0]=='_id'){
				if($search[1]=='in'){
					foreach($search[2] as &$value){
						$value=$this->_formatMongoId($value);
					}
				}
				else{
					$search[2]=$this->_formatMongoId($search[2]);
				}
			}
			// Single query
			if(is_string($search[0])){
				$query[]=$composeCondition($search[0],$search[1],$search[2]);
			}
			// OR queries
			elseif(is_array($search[0])){
				$subs=array();
				foreach($search as $sub){
					$subs[]=$composeCondition($sub[0],$sub[1],$sub[2]);
				}
				if(count($subs)>1){
					$query[]=array('$or'=>$subs);
				}
				else{
					$query[]=$subs[0];
				}
			}
			else{
				throw new Exception("Invalid search() parameter");
			}
		}
		switch(count($query)){
			case 0:
				return $query;
				break;
			case 1:
				return $query[0];
				break;
			default:
				return array('$and'=>$query);
		}
	}

	/*
		Get the current value in the iteration

		Return
			array
	*/
	public function current(){
		$result=$this->cursor->current();
		// Format ID
		if($result['_id']){
			$result['_id']=(string)$result['_id'];
		}
		return $result;
	}

	/*
		Get the current key in the iteration

		Return
			integer
	*/
	public function key(){
		return $this->cursor->key();
	}

	/*
		Iterate
	*/
	public function next(){
		$this->cursor->next();
	}

	/*
		Rewind the iteration
	*/
	public function rewind(){
		// Force closing cursor
		$this->cursor=null;
		// Init cursor
		$this->_initCursor();
		$this->cursor->rewind();
	}

	/*
		Verify if the current position is valid

		Return
			boolean
	*/
	public function valid(){
		return $this->cursor->valid();
	}

}
