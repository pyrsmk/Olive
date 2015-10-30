<?php

namespace Olive\Pdo;

use PDO;
use Olive\Exception;
use Olive\Exception\DatabaseError;
use Olive\AbstractDataContainer;

/*
	Table data container
*/
class Table extends AbstractDataContainer{

	/*
		Return a new query object

		Return
			Olive\Pdo\Query
	*/
	protected function _getNewQuery(){
		return new Query;
	}

	/*
		Insert a row

		Parameters
			array $data		: data to insert
			array $options	: driver options

		Return
			mixed
	*/
	public function insert(array $data){
		try{
			// Get options
			$options=func_num_args()>1?
					 (array)func_get_arg(1):
					 array();
			// Prepare markers
			$data=$this->database->prepareData($data);
			$names=array();
			$markers=array();
			foreach($data as $name=>$value){
				$names[]=$this->database->escape($name);
				$markers[]=':'.$name;
			}
			// Prepare query
			$query='INSERT INTO '.$this->database->escape($this->name).' ('.implode(',',$names).') VALUES ('.implode(',',$markers).')';
			// Insert new row
			$this->database->getDriver()
						   ->prepare($query,$options)
						   ->execute($data);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		return $this->database->getDriver()
							  ->lastInsertId();
	}

	/*
		Save a row

		Parameters
			array $data		: data to save
			array $options	: driver options
	*/
	public function save(array $data){
		try{
			// Get options
			$options=func_num_args()>1?
					 (array)func_get_arg(1):
					 array();
			// Prepare data
			$data=$this->database->prepareData($data);
			$markers=array();
			$fields=array();
			$update_values=array();
			$execute_values=array();
			foreach($data as $name=>$value){
				$marker=$this->database->getMarker();
				$markers[]=':'.$marker;
				$fields[]=$this->database->escape($name);
				$update_values[]=$name.'=:'.$marker;
				$execute_values[$marker]=$value;
			}
			// Prepare query
			$query='INSERT INTO '.$this->database->escape($this->name).
				   ' ('.implode(',',$fields).
				   ') VALUES ('.implode(',',$markers).
				   ') ON DUPLICATE KEY UPDATE '.implode(',',$update_values);
			// Save row
			$statement=$this->database->getDriver()->prepare($query,$options);
			$statement->execute($execute_values);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		$id=$this->database->getDriver()
						   ->lastInsertId();
		if($id){
			return $id;
		}
	}

}
