<?php

namespace Olive;

use Olive\Pdo;

/*
    MS SQL Server adapter

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Mssql extends Pdo{

    /*
        Generate the DSN for that adapter

        Parameters
            string $name    : database name
            array $options  : database options
    */
    protected function _getDsn($name,$options){
        return "sqlsrv:database=$name;".$this->_concatenateOptions($options);
    }

}
