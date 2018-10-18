<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    function getUserIP()
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        
        if( filter_var( $client, FILTER_VALIDATE_IP) ){
            $ip = $client;
        }
        elseif( filter_var($forward,FILTER_VALIDATE_IP) ){
            $ip = $forward;
        }
        else{
            $ip = $remote;
        }
        return $ip;
    }
    
    function getRealUserIP()
    {
        $result = NULL;
        if( !empty( $_SERVER['HTTP_X_REAL_IP'] ) ){
            $result = $_SERVER['HTTP_X_REAL_IP'];
        }
        elseif( !empty ( $_SERVER['HTTP_CLIENT_IP'] ) ){
            $result = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif( !empty ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){
            $result = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else{
            $result = $_SERVER['REMOTE_ADDR'];
        }
        return $result;
    }
    
    function getLocationInfoByIp($ip)
    {
        $result = NULL;
        $ip_data = @json_decode( file_get_contents( "http://www.geoplugin.net/json.gp?ip=".$ip ) );
        if($ip_data && $ip_data->geoplugin_countryName != null)
        {
            $result = $ip_data;
        }
        echo $result;
    }

?>