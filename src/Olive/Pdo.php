<?php

namespace Olive;

use Olive\AbstractDatabase;
use Olive\Pdo\Table;
use Olive\Exception\DatabaseError;

/*
	PDO adapter
*/
abstract class Pdo extends AbstractDatabase{

	/*
		integer $marker : the marker index, used to generate marker for query inputs
	*/
	protected $marker=0;

	/*
		Connect to the server and select a database

		Parameters
			string $name			: database name
			array $options			: database options
			array $driver_options	: driver options
	*/
	public function __construct($name,array $options=array()){
		// Get driver options
		$driver_options=array();
		if(func_num_args()>2){
			$driver_options=(array)func_get_arg(2);
		}
		// Extract username and password
		if($username=$options['username']){
			unset($options['username']);
		}
		if($password=$options['password']){
			unset($options['password']);
		}
		// Create PDO object
		try{
			$this->driver=new \PDO($this->_getDsn($name,$options),$username,$password,$driver_options);
			$this->driver->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
	}

	/*
		Generate the DSN for that adapter

		Parameters
			string $name    : database name
			array $options  : DSN options

		Return
			string
	*/
	abstract protected function _getDsn($name,$options);

	/*
		Concatenate options

		Parameters
			string $name    : database name
			array $options  : DSN options

		Return
			string
	*/
	protected function _concatenateOptions($options){
		$elements=array();
		foreach($options as $name=>$value){
			$elements[]=$name.'='.$value;
		}
		return implode(';',$elements);
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\Pdo\Table
	*/
	public function getDataContainer($name){
		return new Table($this,$name);
	}

	/*
		Return all container names

		Return
			array
	*/
	public function getDataContainerNames(){
		try{
			$query=$this->driver->query('SHOW TABLES');
			$names=$query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();
		}
		catch(\Exception $e){
			throw new DatabaseError($e->getMessage());
		}
		return $names;
	}

	/*
		Escape a string for a SQL request

		Parameters
			string $str

		Return
			string
	*/
	public function escape($str){
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

	/*
		Generate a new marker

		Return
			string
	*/
	public function getMarker(){
		return 'marker'.(++$this->marker);
	}

	/*
		Linearize values if needed

		Return
			array
	*/
	public function prepareData(array $data){
		foreach($data as $name=>&$value){
			if(is_array($value)){
				$value=serialize($value);
			}
			else if(is_string($value) && strpos($value,'##array##')===0){
				$value=unserialize($value);
			}
		}
		return $data;
	}

}
