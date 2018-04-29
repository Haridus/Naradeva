<?php
	function Encrypt($password, $data)
	{
	    $salt = substr(md5(mt_rand(), true), 8);

	    $key = md5($password . $salt, true);
	    $iv  = md5($key . $password . $salt, true);

	    $ct = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);

	    return  rtrim( strtr ( base64_encode('Salted__' . $salt . $ct),'+/','-_'),'=');
	}

//echo Encrypt('myPass123', 'Welcome to Flippancy 25');
// Output: U2FsdGVkX19LYv5Y5EDmFbjH8bGMDFwlid30h2x1ybibT1Dwp0vekJ0OT4tb7/j6

	function Decrypt($password, $data)
	{

	    $data = base64_decode( str_pad( strtr($data,'-_','+/'), strlen($data)%4, '=', STR_PAD_RIGHT ) );
	    $salt = substr($data, 8, 8);
	    $ct   = substr($data, 16);

	    $key = md5($password . $salt, true);
	    $iv  = md5($key . $password . $salt, true);

	    $pt = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ct, MCRYPT_MODE_CBC, $iv);

	    return $pt;
	}

//echo Decrypt('myPass123', 'U2FsdGVkX19LYv5Y5EDmFbjH8bGMDFwlid30h2x1ybibT1Dwp0vekJ0OT4tb7/j6');
//echo Decrypt('myPass123', 'U2FsdGVkX1/3zxJCcE8p89t67nJNp8blNkezNxTVn4IDFQLM755K2+OSfFHewDLI');
//echo Decrypt('myPass123', 'U2FsdGVkX18OQ8puUN8BBi+d6vAjEzDTZqM2WaKQD1atOykkYl9MY7NQM1DqI4Kw');

	function hashPassword($password, $salt) {
	    $hash_password = hash("SHA512", base64_encode(str_rot13(hash("SHA512", str_rot13($salt . $password)))));
	    return $hash_password;
	}

//echo hashPassword('myPa55w0rd', '[email protected]');

?>