<?php

namespace Olive;

use ArrayAccess;
use Closure;

/*
    Core class

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
abstract class Database implements ArrayAccess{

    /*
        array $log          : logging callback stack
    */
    static public $log=array();

    /*
        mixed $db           : database object
        string $namespace   : namespace
        Olive\Cache $cache  : cache object
    */
    protected $db;
    protected $namespace;
    protected $cache;

    /*
        Connect to the server and select a database

        Parameters
            string $name    : database name
            array $options  : database options
    */
    public function __construct($name,array $options=array()){
        // Init database
        $this->_init($name,$options);
        // Set default cache handler
        $this->cache=function($key,$lifetime,$getResults){
            return $getResults();
        };
    }

    /*
        Init database
        
        Parameters
            string $name
            array $options
    */
    abstract protected function _init($name,array $options);

    /*
        Return the database object

        Return
            mixed
    */
    final public function getEngine(){
        return $this->db;
    }

    /*
        Set the namespace

        Parameters
            string $namespace

        Return
            Olive
    */
    final public function setNamespace($namespace){
        $this->namespace=(string)$namespace;
        return $this;
    }

    /*
        Get the namespace

        Return
            string
    */
    final public function getNamespace(){
        return $this->namespace;
    }

    /*
        Set cache functions
        
        Parameters
            Closure $cache
        
        Return
            Olive
    */
    final public function setCache(Closure $cache){
        $this->cache=$cache;
        return $this;
    }

    /*
        Add a callback to the log stack

        Return
            Olive
    */
    final public function log($callback){
        self::$log[]=$callback;
        return $this;
    }

    /*
        Return a container

        Parameters
            string $name

        Return
            Olive\Container
    */
    abstract public function getContainer($name);

    /*
        Return all container IDs

        Return
            array
    */
    abstract public function getContainerNames();

    /*
        Return a container

        Parameters
            string $name

        Return
            Olive\Container
    */
    final public function __get($name){
        return $this->getContainer($this->getNamespace().(string)$name);
    }

    /*
        Return a container

        Parameters
            string $name

        Return
            Olive\Container
    */
    final public function offsetGet($name){
        return $this->$name;
    }

    /*
        Verify if a value exists (disabled)
    */
    final public function offsetExists($name){}

    /*
        Set a value (disabled)
    */
    final public function offsetSet($name,$container){}

    /*
        Remove a value (disabled)
    */
    final public function offsetUnset($name){}

}
