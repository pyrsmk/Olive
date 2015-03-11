<?php

namespace Olive;

use Olive\Pdo;

/*
    MySQL adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Mysql extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        return "mysql:dbname=$name;".$this->_concatenateOptions($options);
    }

}
