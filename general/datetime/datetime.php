<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    function date_is_valid($date)
    {
        $invalid_date = date_create("1800-01-01");
        return $date > $invalid_date;
    }
    
    function date_to_category($date)
    {
        $category = NULL;
        if( date_is_valid($date) ){
            $category = 0;        
            $now   = date_create();
            $interval = date_diff($date, $now);

            if( ( $interval->y >= 7 ) and ( $interval->y < 14 ) ){
                $category = 1;
            }
            elseif( ($interval->y >= 14) and ($interval->y < 22) ){
                $category = 2;
            }
            elseif($interval->y >= 22){
                $category = 3;
            }
        }
        
        return $category;
    }
    
    function date_is_in_category($date, $category)
    {
        $result = FALSE;
        if(date_is_valid($date) ){
            $dcat = date_to_category($date);
            $result = $dcat == $category;
        }
        return $result;
    }

