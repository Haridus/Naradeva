<?php
	require_once('LocalSettings.php');
	require_once('Mysql.php');
	require_once('Pgsql.php');
	require_once('Sqlite.php');
	
	
	function getDefaultConfig()
	{
		$config = array(
		                'database' => AppSettings::dbDatabase,
		                'host' =>     AppSettings::dbDefaultHost,
		                'port' =>     AppSettings::dbDefaultPort,
		                'username' => AppSettings::dbDefaultUser,
		                'password' => AppSettings::dbDefaultPassword,
		                'options' => array()
		                 );
		return $config;
	}
	
	function getDefaultAdaptor()
	{
		return new Mysql();
	}
	
	$g_defConnect = NULL;
	function getDefaultConnect()
	{
		global $g_defConnect;
		if( !$g_defConnect ){
                    $config = getDefaultConfig();
                    $adaptor = getDefaultAdaptor();
		    $g_defConnect = $adaptor->connect($config);
		}
		return $g_defConnect;
        }
?>