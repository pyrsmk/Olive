<?php

namespace Olive;

use Olive\Pdo;

/*
    CUBRID adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Cubrid extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        if(!($host=$options['host'])){
            $host='localhost';
        }
        if(!($port=$options['port'])){
            $port=33000;
        }
        return "cubrid:host=$host;port=$port;dbname=$name";
    }

}
