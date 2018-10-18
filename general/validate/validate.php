<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    function validate_login($login)
    {
        $result = FALSE;
        $len = strlen($login);
        if( $len>0 and $len<=32){
            $regexp = "/^[^\\d\\s\\W][\\w\\d]*$/";
            $result = preg_match($regexp, $login);
        }
        return $result;
    }
    
    function validate_mail($mail)
    {
        $result = FALSE;
        $len = strlen($mail);
        if( $len > 0 and $len <= 128 ){
            $result = filter_var($mail, FILTER_VALIDATE_EMAIL);
        }
        return $result;
    }
    
    function validate_phone($phone)
    {
        $result = FALSE;
        $len = strlen($phone);
        if( $len >0  and $len <=32 ){
            $regexp = "/^\\+{0,1}(\\d{1,4}-{0,1}){0,1}(\\d{1,4}-{0,1}){0,1}(\\d{3,3}-{0,1})(\\d{2,2}-{0,1})(\\d{2,2}-{0,1})$/";
            $result = preg_match($regexp, $phone);
        }
        return $result;
    }

?>

