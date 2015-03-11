<?php

namespace Olive;

use Olive\Database;
use Olive\Pdo\Table;
use Olive\Exception\DatabaseError;

/*
    PDO adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)

    PHP dependencies
        PDO
*/
abstract class Pdo extends Database{

    /*
        Connect to the server and select a database

        Parameters
            string $name    : database name
            array $options  : DSN options
            array           : driver options

        Throw
            Olive\Exception\DatabaseError
    */
    protected function _init($name,array $options=array()){
        // Get driver options
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
            $this->db=new \PDO($this->_getDsn($name,$options),$username,$password,(array)$driver_options);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
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
    public function getContainer($name){
        return new Table($this,$name,$this->cache);
    }

    /*
        Return all container IDs

        Return
            array

        Throw
            Olive\Exception\DatabaseError
    */
    public function getContainerNames(){
        try{
            $query=$this->db->query('SHOW TABLES');
            $names=$query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }
        catch(\Exception $e){
            throw new DatabaseError($e->getMessage());
        }
        return $names;
    }

    /*
        Begin a transaction

        Return
            Olive\Mysql

        Throw
            Olive\Exception\DatabaseError
    */
    public function begin(){
        try{
            $this->db->beginTransaction();
        }
        catch(\Exception $e){
            throw new DatabaseError($this->db);
        }
    }

    /*
        Commit a transaction

        Return
            Olive\Mysql

        Throw
            Olive\Exception\DatabaseError
    */
    public function commit(){
        try{
            $this->db->commit();
        }
        catch(\Exception $e){
            throw new DatabaseError($this->db);
        }
    }

    /*
        Rollback a transaction

        Return
            Olive\Mysql

        Throw
            Olive\Exception\DatabaseError
    */
    public function rollback(){
        try{
            $this->db->rollBack();
        }
        catch(\Exception $e){
            throw new DatabaseError($this->db);
        }
    }

}
