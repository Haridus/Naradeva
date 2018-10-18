<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    function sql_field_string($string)
    {
        $result = NULL;
        if( strlen($string) > 0 ){
            $result = "'$string'";
        }
        return $result;
    }

    function request_build_operation_string($operation,$session=NULL)
    {
        $baseUrl = AppSettings::baseUrl;
        $appkey  = AppSettings::appkey;
        $entryes = [];
        $entryes[] = "$baseUrl/?appkey=$appkey";
        $entryes[] = "operation=$operation";
        if( $session and strlen($session) > 0 ){
            $entryes[] = "session=$session";
        }
                
        return implode("&", $entryes);
    }

?>