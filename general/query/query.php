<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    function prepare_and_execute($dblink,$query,$binds = array())
    {
        $result = NULL;
        if( ( $stmt = $dblink->prepare($query) ) ){
            foreach( $binds as $key => $value ){
                $stmt->bindValue($key,$value);
            }
            if( ( $stmt->execute() ) ){
                $result = $stmt; 
            }
            else{
                debug( sprintf("[error:%d]%s(%s)",$stmt->errorCode(), implode(",",$stmt->errorInfo()),$query) );
                $stmt = NULL;
            }
        }
        else{
            debug( sprintf("[error:%d]%s",$dblink->errorCode(),implode(",",$dblink->errorInfo())) );
        }
        return $result;
    }
    
    function onDuplicateClause($data)
    {
        $setEntryes = array();
        foreach ($data as $key => $value) {
            $setEntryes[] = "$key=$value";
        }
        $setClause = implode(",", $setEntryes);
        return "ON DUPLICATE KEY UPDATE $setClause";
    }
    
?>

