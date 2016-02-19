<?php

namespace Olive\Mongodb;

use MongoId;
use MongoCollection;
use Olive\Exception;
use Olive\AbstractDataContainer;
use Olive\Mongodb as Database;

/*
	Collection data container
*/
class Collection extends AbstractDataContainer {

	/*
		Return a new query object

		Return
			Olive\Mongodb\Query
	*/
	protected function _getNewQuery() {
		return new Query($this->database, $this->name);
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
			$bulk = new \MongoDB\Driver\BulkWrite;
			$id = (string)$bulk->insert($document);
			$this->database->getDriver()->executeBulkWrite($this->name, $bulk);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
		}
		return $id;
	}

	/*
		Insert or update an element

		Parameters
			array $document : document to insert or update
			array $options  : driver options
	*/
	public function save(array $document) {}

}
