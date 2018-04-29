<?php
	require_once('LocalSettings.php');
	require_once('global.php');

	$g_dblink; 
		
	function initializeDB() : bool 
	{
		global $g_dblink;
		$result = false;
		
		$g_dblink = new mysqli(AppSettings::dbDefaultHost,AppSettings::dbDefaultUser,AppSettings::dbDefaultPassword,AppSettings::dbDatabase);
		
		$result = boolval( $g_dblink->connect_errno ) ;	
		return $result;
	}
	
	function deinitializeDB()
	{
		global $g_dblink;
		$g_dblink->close();
	}
	
	function initialize() : bool
	{
		$result = true;
		$result = $result and initializeDB();
		return $result;
	}
	
	function deinitialize()
	{
		deinitializeDB();
	}
?>