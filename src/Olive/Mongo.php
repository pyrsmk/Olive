<?php

namespace Olive;

use Olive\Database;
use Olive\Mongo\Collection;
use Olive\Exception;
use Olive\Exception\DatabaseError;

/*
    MongoDB adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)

    PHP dependencies
        Mongo
*/
class Mongo extends Database{

    /*
        boolean $write_concern
        boolean $auto_format_ids
    */
    protected $write_concern=1;
    protected $auto_format_ids=true;

    /*
        Connect to the server and select a database

        Parameters
            string $name    : database name
            array $options  : database options

        Throw
            Olive\Exception\DatabaseError
    */
    protected function _init($name,array $options=array()){
        if(!($hosts=(array)$options['hosts'])){
            $hosts['localhost']=27017;
        }
        try{
            // Generate host list
            $servers=array();
            foreach($hosts as $host=>$port){
                $servers[]=$host.':'.$port;
            }
            unset($options['hosts']);
            // Generate auth chain
            if(($username=$options['username']) && ($password=$options['password'])){
                $auth=urlencode($username).':'.urlencode($password).'@';
            }
            unset($options['username']);
            unset($options['password']);
            // Instantiate driver
            $mongo=new \Mongo('mongodb://'.$auth.implode(',',$servers).'/'.urlencode($name),$options);
            // Select database
            $this->db=$mongo->$name;
        }
        catch(\Exception $e){
            throw new DatabaseError($e->getMessage());
        }
    }

    /*
        Return a container

        Parameters
            string $name

        Return
            Olive\Mongo\Collection
    */
    public function getContainer($name){
        return new Collection($this,$name,$this->cache);
    }

    /*
        Return all container IDs

        Return
            array
    */
    public function getContainerNames(){
        $names=array();
        foreach($this->db->listCollections() as $collection){
            $names[]=(string)$collection;
        }
        return $names;
    }

    /*
        Set/get write concern policy

        Parameters
            boolean $flag

        Return
            boolean, Olive\Mongo
    */
    public function writeConcern($flag=null){
        if($flag===null){
            return $this->write_concern;
        }
        else{
            $this->write_concern=(bool)$flag;
            return $this;
        }
    }

    /*
        Set/get auto format IDs

        Parameters
            boolean $flag

        Return
            boolean, Olive\Mongo
    */
    public function autoFormatIds($flag=null){
        if($flag===null){
            return $this->auto_format_ids;
        }
        else{
            $this->auto_format_ids=(bool)$flag;
            return $this;
        }
    }

}
