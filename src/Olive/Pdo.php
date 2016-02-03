<?php

namespace Olive;

use Olive\Pdo\Table;

/*
	PDO adapter
*/
abstract class Pdo extends AbstractDatabase{

	/*
		integer $marker : the marker index, used to generate marker for query inputs
	*/
	protected $marker=0;
	
	/*
		Initialize the database
		
		Parameters
			string $name	: database name
			array $options	: database options
	*/
	protected function _initDatabase($name, $options) {
		// Get driver options
		$driver_options=array();
		if(func_num_args()>2){
			$driver_options=(array)func_get_arg(2);
		}
		// Extract username and password
		$username = isset($options['username']) ? $options['username'] : null;
		if(isset($options['username'])) {
			unset($options['username']);
		}
		$password = isset($options['password']) ? $options['password'] : null;
		if(isset($options['password'])) {
			unset($options['password']);
		}
		// Create PDO object
		try{
			$this->driver=new \PDO($this->_getDsn($name,$options),$username,$password,$driver_options);
			$this->driver->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
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
			$names=$query->fetchAll(\PDO::FETCH_COLUMN);
			$query->closeCursor();
		}
		catch(\Exception $e){
			throw new Exception($e->getMessage());
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
			if(isset($function)){
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

}
