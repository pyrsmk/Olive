<?php

namespace Olive;

use Olive\Pdo;
use Olive\Sqlite\Table;
use Olive\Exception\DatabaseError;

/*
    SQLite adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Sqlite extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        if($options['sqlite2']){
            return 'sqlite2:'.$name;
        }
        else{
            return 'sqlite:'.$name;
        }
    }

    /*
        Return a container

        Parameters
            string $name

        Return
            Olive\Sqlite\Table
    */
    public function getContainer($name){
        return new Table($this,$name,$this->cache);
    }

    /*
        Return all container IDs

        Return
            array

        Throw
            Olive\Pdo\DatabaseError
    */
    public function getContainerNames(){
        // Retrieve schema informations
        try{
            $query=$this->db->query('SELECT name FROM sqlite_master WHERE type="table" AND name<>"sqlite_sequence"');
            $results=$query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }
        catch(\Exception $e){
            throw new DatabaseError($e->getMessage());
        }
        // Clean up
        $names=array();
        foreach($results as $result){
            $names[]=$result['name'];
        }
        return $names;
    }

}
