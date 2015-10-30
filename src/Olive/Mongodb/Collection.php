<?php

namespace Olive\Mongodb;

use MongoId;
use MongoCollection;
use Olive\Exception;
use Olive\Exception\DatabaseError;
use Olive\AbstractDataContainer;

/*
	Collection data container
*/
class Collection extends AbstractDataContainer{

	/*
		MongoCollection $collection : collection
	*/
	protected $collection;

	/*
		Constructor

		Parameters
			Olive\Database $database
			mixed $name
	*/
	public function __construct(Database $database,$name){
		parent::__construct($database,$name);
		$this->collection=$this->database->getDriver()->$name;
	}

	/*
		Return a new query object

		Return
			Olive\Mongodb\Query
	*/
	protected function _getNewQuery(){
		return new Query;
	}

	/*
		Insert a document

		Parameters
			array $document : document values
			array           : driver options

		Return
			string
	*/
	public function insert(array $document){
		// Get options
		$options=func_num_args()>1?
				 (array)func_get_arg(1):
				 array();
		// Insert the new document
		try{
			$this->collection->insert($document,$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		return (string)$document['_id'];
	}

	/*
		Insert or update an element

		Parameters
			array $document : document to insert or update
			array $options  : driver options
	*/
	public function save(array $document){
		// Get options
		$options=func_num_args()>1?
				 (array)func_get_arg(1):
				 array();
		// Save document
		try{
			$this->collection->save($document,$options);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
	}

}
