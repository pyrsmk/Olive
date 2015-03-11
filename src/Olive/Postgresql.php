<?php

namespace Olive;

use Olive\Pdo;

/*
    PostgreSQL adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Postgresql extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        return "pgsql:dbname=$name;".$this->_concatenateOptions($options);
    }

}
