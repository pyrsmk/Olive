<?php

namespace Olive;

use Olive\Pdo;

/*
    Firebird adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Firebird extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        return "firebird:dbname=$name;".$this->_concatenateOptions($options);
    }

}
