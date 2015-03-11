<?php

namespace Olive\Sqlite;

use Olive\Pdo\Table as PdoTable;
use Olive\Exception\DatabaseError;

/*
    Sqlite table data container

    Author
        Aurélien Delogu (dev@dreamysource.fr)
*/
class Table extends PdoTable{

    /*
        Save a row

        Parameters
            array $values   : values
            array           : driver options

        Return
            mixed

        Throw
            Olive\Exception\DatabaseError
    */
    public function save($values){
        $this->_verifyQueryInit();
        try{
            // Format
            $values=(array)$values;
            if(func_num_args()>1){
                $options=(array)func_get_arg(1);
            }
            else{
                $options=array();
            }
            // Prepare data
            $search=$this->query['search'];
            $search_values=array();
            foreach($search as $value){
                if(strtolower($value[1])!='is'){
                    throw new Exception("Forbidden use of operators other than 'is' with save operation");
                }
                $search_values[$value[0]]=$value[2];
            }
            // Update
            if($result=$this->fetchOne()){
                $this->query['search']=$search;
                $r=$this->update($values);
            }
            // Insert
            else{
                $r=$this->insert(array_merge($values,$search_values));
            }
        }
        catch(\Exception $e){
            throw new DatabaseError($e->getMessage());
        }
        $this->_closeCursor();
        return $r;
    }

}
