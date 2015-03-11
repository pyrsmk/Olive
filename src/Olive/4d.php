<?php

namespace Olive;

use Olive\Pdo;

/*
    4D adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class 4d extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        return '4D:'.$this->_concatenateOptions($options);
    }

}
