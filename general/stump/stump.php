<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once './general/encrypt_decrypt/encrypt_decrypt_string.php';
require_once './LocalSettings.php';

function make_obj_stump_string($data)
{
    $result = NULL;
    if( is_array($data) and count($data) > 0 ){
        $entryes = array();
        foreach ($data as $key => $value) {
            $entryes[] = "$key=$value"; 
        }
        $result = implode("&",$entryes);
    }
    return $result;
}

function parse_obj_stump_string($stumpString)
{
    parse_str( $stumpString,$reqData );
    return $reqData;
}

function makeStump($stumpSeed)
{
    $stump=Encrypt(AppSettings::stumpKey, $stumpSeed);
    return $stump;
}
	
function decodeStump($stump)
{
    $data = Decrypt(AppSettings::stumpKey, $stump);
    return $data;
}

function make_obj_stump($data)
{
    $stumpString = make_obj_stump_string($data);
    return makeStump($stumpString);
}

function decode_obj_stump($stump)
{    
    $stumpString = decodeStump($stump);
    $data = parse_obj_stump_string($stumpString);
    return $data;
}

function make_user_stump($data)
{
    $kv = array('type' => $data['type' ],
                'login'=> $data['login'],
                'mail' => $data['mail' ],
                'phone'=> $data['phone']
                );
    $stump = make_obj_stump($kv);
    return $stump;
}

function update_stump($stump, $data)
{
    $stumpData = decode_obj_stump($stump);
    
    foreach ($data as $key => $value){
        if( array_key_exists($key, $stumpData) ){
            $stumpData[$key] = $value;
        }
    }
        
    $new_stump = make_obj_stump($stumpData);
    return $new_stump;
}

?>

