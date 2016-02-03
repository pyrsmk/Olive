<?php

namespace Olive;

use ArrayAccess;

/*
	Core database class
*/
abstract class AbstractDatabase implements ArrayAccess{

	/*
		object $driver		: the database driver
		string $namespace	: namespace
	*/
	protected $driver;
	protected $namespace;

	/*
		Connect to the server and select a database

		Parameters
			string $name	: database name
			array $options	: database options
	*/
	public function __construct($name, array $options = array()) {
		if(!$this::isSupported()) {
			throw new Exception("Database type not supported");
		}
		$this->_initDatabase($name, $options);
	}
	
	/*
		Initialize the database
		
		Parameters
			string $name	: database name
			array $options	: database options
	*/
	abstract protected function _initDatabase($name, $options);
	
	/*
		Verify if the adapter is supported by the environment
		
		Return
			boolean
	*/
	abstract static public function isSupported();

	/*
		Return the database driver

		Return
			object
	*/
	public function getDriver(){
		return $this->driver;
	}

	/*
		Set the namespace

		Parameters
			string $namespace

		Return
			Olive\AbstractDatabase
	*/
	public function setNamespace($namespace){
		$this->namespace=(string)$namespace;
		return $this;
	}

	/*
		Get the namespace

		Return
			string
	*/
	public function getNamespace(){
		return $this->namespace;
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\AbstractDataContainer
	*/
	abstract public function getDataContainer($name);

	/*
		Return all container names

		Return
			array
	*/
	abstract public function getDataContainerNames();

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\AbstractDataContainer
	*/
	public function __get($name){
		return $this->getDataContainer((string)$name);
	}

	/*
		Return a container

		Parameters
			string $name

		Return
			Olive\AbstractDataContainer
	*/
	public function offsetGet($name){
		return $this->$name;
	}

	/*
		Verify if a value exists (disabled)
	*/
	public function offsetExists($name){}

	/*
		Set a value (disabled)
	*/
	public function offsetSet($name,$container){}

	/*
		Remove a value (disabled)
	*/
	public function offsetUnset($name){}

}
