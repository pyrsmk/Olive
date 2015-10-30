<?php

namespace Olive\Sqlite;

use Olive\Pdo\Table as PdoTable;
use Olive\Exception\DatabaseError;

/*
	Sqlite table data container
*/
class Table extends PdoTable{

	/*
		Save a row

		Parameters
			array $data : data to save
			array 		: driver options

		Return
			mixed
	*/
	public function save(array $data){
		// Format
		$options=array();
		if(func_num_args()>1){
			$options=(array)func_get_arg(1);
		}
		try{
			// Create query
			$query=$this->_getNewQuery();
			foreach($data as $name=>$value){
				$query->search($name,'is',$value);
			}
			// Update
			if($query->count()){
				$result=$this->update($data,$options);
			}
			// Insert
			else{
				$result=$this->insert($data,$options);
			}
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		return $result;
	}

}
