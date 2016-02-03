<?php

namespace Olive;

use Olive\AbstractDatabase as Database;

/*
	Abstract model class
*/
abstract class Model{

	/*
		Olive\Database $database
	*/
	protected $database;

	/*
		Constructor

		Parameters
			Olive\Database $database
	*/
	public function __construct(Database $database){
		$this->database=$database;
		if(!$this->singular){
			throw new Exception("'singular' property must be defined");
		}
		if(!$this->plural){
			throw new Exception("'plural' property must be defined");
		}
		if(!$this->data_container){
			throw new Exception("'data_container' property must be defined");
		}
		if(!$this->primary_key){
			throw new Exception("'primary_key' property must be defined");
		}
	}

	/*
		Call a model method

		Parameters
			string $name
			array $parameters

		Return
			mixed
	*/
	public function __call($method,$parameters){
		try{
			// Get model name
			$singular=ucfirst(strtolower($this->singular));
			$plural=ucfirst(strtolower($this->plural));
			// Split camelized called method
			$parts=preg_split('#(?=[A-Z])#',$method);
			$count=count($parts);
			// Verify existence
			if(ucfirst($parts[0])==$singular && $parts[1]=='Exists' && $count<=4){
				if($count==4){
					if($parts[2]!='By') throw new Exception();
					$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
				}
				return $this->_oneExists($parameters[0]);
			}
			elseif(ucfirst($parts[0])==$plural && $parts[1]=='Exist' && $count<=4){
				if($count==4){
					if($parts[2]!='By') throw new Exception();
					$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
				}
				return $this->_severalExist($parameters[0]);
			}
			// Count elements
			elseif($parts[0]=='count' && $parts[1]==$plural && $count<=4){
				if($count==4){
					if($parts[2]!='By') throw new Exception();
					$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
				}
				return $this->_count($parameters[0]);
			}
			// Insert a new element
			elseif(($parts[0]=='insert' || $parts[0]=='add') && $count==2){
				if($parts[1]==$singular){
					return $this->_insertOne($parameters[0]);
				}
				elseif($parts[1]==$plural){
					return $this->_insertSeveral($parameters[0]);
				}
			}
			// Get one or more elements
			elseif($parts[0]=='get' && $count<=5){
				switch($count){
					case 3:
						$parameters[1]=array(strtolower($parts[2]));
						break;
					case 4:
						if($parts[2]!='By') throw new Exception();
						$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
						break;
					case 5:
						if($parts[3]!='By') throw new Exception();
						$parameters[0]=array(strtolower($parts[4])=>$parameters[0]);
						$parameters[1]=array(strtolower($parts[2]));
						break;
				}
				if($parts[1]==$singular){
					return $this->_getOne($parameters[0],$parameters[1]);
				}
				elseif($parts[1]==$plural){
					return $this->_getSeveral($parameters[0],$parameters[1]);
				}
			}
			// Update one or more elements
			elseif($parts[0]=='update' && $count<=5){
				switch($count){
					case 3:
						$parameters[1]=array(strtolower($parts[2])=>$parameters[1]);
						break;
					case 4:
						if($parts[2]!='By') throw new Exception();
						$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
						break;
					case 5:
						if($parts[3]!='By') throw new Exception();
						$parameters[0]=array(strtolower($parts[4])=>$parameters[0]);
						$parameters[1]=array(strtolower($parts[2])=>$parameters[1]);
						break;
				}
				if($parts[1]==$singular){
					return $this->_updateOne($parameters[0],$parameters[1]);
				}
				elseif($parts[1]==$plural){
					return $this->_updateSeveral($parameters[0],$parameters[1]);
				}
			}
			// Save one element
			elseif(($parts[0]=='save' || $parts[0]=='set') && $count==2){
				if($parts[1]==$singular){
					return $this->_saveOne($parameters[0]);
				}
				elseif($parts[1]==$plural){
					throw new Exception("Saving/setting several rows is forbidden");
				}
			}
			// Remove one or more elements
			elseif(($parts[0]=='remove' || $parts[0]=='delete') && $count<=4){
				if($count==4){
					if($parts[2]!='By') throw new Exception();
					$parameters[0]=array(strtolower($parts[3])=>$parameters[0]);
				}
				if($parts[1]==$singular){
					return $this->_removeOne($parameters[0]);
				}
				elseif($parts[1]==$plural){
					return $this->_removeSeveral($parameters[0]);
				}
			}
			throw new Exception();
		}
		catch(Exception $e){
			if($e->getMessage()){
				throw new Exception($e->getMessage());
			}
			throw new Exception("Unsupported '$method' method called");
		}
	}

	/*
		Verify if one row exists

		Parameters
			integer, string, array $search

		Return
			boolean
	*/
	protected function _oneExists($search){
		$this->_verifyOneSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareOneSearch($request,$search);
		return (bool)$request->select($this->primary)
							 ->fetchOne();
	}

	/*
		Verify if several rows exist

		Parameters
			array $search

		Return
			boolean
	*/
	protected function _severalExist($search){
		$this->_verifySeveralSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareSeveralSearch($request,$search);
		return (bool)$request->select($this->primary)
							 ->fetchOne();
	}

	/*
		Counts occurences

		Parameters
			array $search

		Return
			integer
	*/
	protected function _count($search){
		$this->_verifySeveralSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareSearch($request,$search);
		return $request->count();
	}

	/*
		Get one row

		Parameters
			integer, string, array $search
			array $select

		Return
			array
	*/
	protected function _getOne($search,$select){
		$this->_verifyOneSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareOneSearch($request,$search);
		$this->_prepareSelect($request,$select);
		if(count((array)$select)==1){
			return $request->fetchFirst();
		}
		else{
			return $request->fetchOne();
		}
	}

	/*
		Get several rows

		Parameters
			array $search
			array $select

		Return
			array
	*/
	protected function _getSeveral($search,$select){
		$this->_verifySeveralSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareSeveralSearch($request,$search);
		$this->_prepareSelect($request,$select);
		return $request->fetch();
	}

	/*
		Insert one row

		Parameters
			array $data

		Return
			mixed
	*/
	protected function _insertOne($data){
		$this->_verifyData($data);
		$data=$this->_validateFields($data);
		$request=$this->database->getDataContainer($this->container);
		return $request->insert($data);
	}

	/*
		Insert several rows

		Parameters
			array $data

		Return
			array
	*/
	protected function _insertSeveral($data){
		$this->_verifyData($data);
		$ids=array();
		foreach($data as $row){
			$ids[]=$this->_insertOne((array)$row);
		}
		return $ids;
	}

	/*
		Update one row

		Parameters
			integer, string, array $search
			array $data
		
		Return
			integer
	*/
	protected function _updateOne($search,$data){
		$this->_verifyOneSearch($search);
		$this->_verifyData($data);
		$data=$this->_validateFields($data);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareOneSearch($request,$search);
		return $request->update($data);
	}

	/*
		Update several rows

		Parameters
			array $search
			array $data
		
		Return
			integer
	*/
	protected function _updateSeveral($search,$data){
		$this->_verifySeveralSearch($search);
		$this->_verifyData($data);
		$data=$this->_validateFields($data);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareSeveralSearch($request,$search);
		return $request->update($data);
	}

	/*
		Save one row

		Parameters
			array $data
		
		Return
			integer
	*/
	protected function _saveOne($data){
		$this->_verifyData($data);
		return $this->database->getDataContainer($this->container)->save($this->_validateFields($data));
	}

	/*
		Remove one row

		Parameters
			integer, string, array $search
		
		Return
			integer
	*/
	protected function _removeOne($search){
		$this->_verifyOneSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareOneSearch($request,$search);
		return $request->remove();
	}

	/*
		Remove several rows

		Parameters
			array $search
		
		Return
			integer
	*/
	protected function _removeSeveral($search){
		$this->_verifySeveralSearch($search);
		$request=$this->database->getDataContainer($this->container);
		$this->_prepareSeveralSearch($request,$search);
		return $request->remove();
	}

	/*
		Prepare search clauses

		Parameters
			Olive\Container $request
			array $search
	*/
	protected function _prepareSearch($request,$search){
		$search=(array)$search;
		if(!$search){
			$request->search();
		}
		else{
			foreach($search as $field=>$value){
				if(is_array($value)){
					$request->search($field,'in',$value);
				}
				else{
					$request->search($field,'is',$value);
				}
			}
		}
	}

	/*
		Prepare search clauses for one result

		Parameters
			Olive\Container $request
			array $search
	*/
	protected function _prepareOneSearch($request,$search){
		if(is_array($search)){
			$this->_prepareSearch($request,$search);
		}
		else{
			$request->search($this->primary,'is',$search);
		}
	}

	/*
		Prepare search clauses for several results

		Parameters
			Olive\Container $request
			array $search
	*/
	protected function _prepareSeveralSearch($request,$search){
		$search=(array)$search;
		if(!$search || is_string(key($search))){
			$this->_prepareSearch($request,$search);
		}
		else{
			$request->search($this->primary,'in',$search);
		}
	}

	/*
		Prepare select clauses

		Parameters
			Olive\Container $request
			array $select
	*/
	protected function _prepareSelect($request,$select){
		foreach((array)$select as $field=>$alias){
			if(is_string($field)){
				$request->select($field,$alias);
			}
			else{
				$request->select($alias);
			}
		}
	}

	/*
		Validate fields

		Parameters
			array $fields

		Return
			array
	*/
	protected function _validateFields($fields){
		foreach($fields as $field=>&$value){
			if(method_exists($this,$method='validate'.ucfirst(strtolower($field)))){
				$value=call_user_func(array($this,$method),$value);
			}
		}
		return $fields;
	}

	/*
		Verify one-search's value type

		Parameters
			mixed $search
	*/
	protected function _verifyOneSearch($search){
		if(!is_int($search) && !is_string($search) && !is_array($search)){
			throw new Exception("'search' parameter must be an integer, either a string or an array");
		}
	}
	
	/*
		Verify several-search's value type

		Parameters
			mixed $search
	*/
	protected function _verifySeveralSearch($search){
		if(!is_null($search) && !is_array($search)){
			throw new Exception("'search' parameter must be an array");
		}
	}
	
	/*
		Verify data's value type

		Parameters
			mixed $data
	*/
	protected function _verifyData($data){
		if(!is_array($data)){
			throw new Exception("'data' parameter must be an array");
		}
	}

}
