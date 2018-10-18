<?php
    
    Class AppSettings
    {	
        CONST installedState = 1;
        
        CONST debugLevel = PHP_INT_MAX;
        
        CONST fOpLog = 0xffffff;
        
        CONST baseUrl = "http://192.168.77.4/civilhelper";
        const appName = "CivilHelper";
        
        CONST dbDefaultHost = "localhost";
	CONST dbDefaultPort = 3306;
	CONST dbDefaultUser = "root";
	CONST dbDefaultPassword= "";
	CONST dbDatabase = "civilhelper";
	
        CONST appDbTablesPrefix = "cvh";
        CONST appDbTablesSuffix = "";
        
        CONST appkey = "777111777";
               	
	CONST defaultSuperadminLogin = "admin";
	CONST defaultSuperadminPass  = "admin";
	CONST defaultSuperadminMail  = "";
	CONST defaultSuperadminPhone = "";
		
	CONST stumpKey = "U2FsdGVkX1_fZ94IG-xEuFGprFitp402UniI7kwyMyc";
	CONST passKey  = "U2FsdGVkX19ZMkxbmEiJyHTG9QvKZ83J-uvZwgIqoVI";
	CONST registerKey = "U2FsdGVkX1-rmRPyFr5Cy0nQ9O7EFDUbPOBRd9sqNVg";
        CONST requestKey = "U2FsdGZ94IG-xEuFGprFitp4bPOBRd9sqNVg";
        
        CONST defaultLocale = 'ru'; //'[language[_territory][.codeset][@modifiers]]'    
        
        CONST sh_min_segment_value = 0.0060;
        CONST dl_min_segment_value = 0.0120; 
    }
	
?>