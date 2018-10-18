<?php
    require_once ('./LocalSettings.php');
    
    class DebugLevels
    {
        CONST DEBUG_LEVEL_0 = 0;
        CONST DEBUG_LEVEL_1 = 0;
        CONST DEBUG_LEVEL_2 = 0;
        CONST DEBUG_LEVEL_3 = 0;
        CONST DEBUG_LEVEL_4 = 0;
        CONST DEBUG_LEVEL_5 = 0;
        
        CONST URGENT_AND_SYSTEM_MSG = DebugLevels::DEBUG_LEVEL_0;
        CONST WARNINGS              = DebugLevels::DEBUG_LEVEL_1;
        CONST PRIMARY_DEBUG_INFO    = DebugLevels::DEBUG_LEVEL_2;
        CONST COMMON_DEBUG_INFO     = DebugLevels::DEBUG_LEVEL_3;
        CONST SECONDARY_DEBUG_INFO  = DebugLevels::DEBUG_LEVEL_4;
        CONST INFO_MESSAGES         = DebugLevels::DEBUG_LEVEL_5;
    }

    class DebugNoteTypes
    {
        CONST DEBUG = 0;
        CONST WARNING = 1;
        CONST FATAL = 2;
    }
    
    function makeLogNote($msg, $type = 0, $clear = FALSE, $path = NULL) : bool
    {
        $result = false;
	if( $msg ){
            $datetime = date('[Y-m-d H:i:s]');
            $typeMark = '[]';
            $flags = 0;
			
            switch($type){
		case DebugNoteTypes::DEBUG:
                    $typeMark = '[debug]';
                    break;
		case DebugNoteTypes::WARNING:
                    $typeMark = '[warning]';
                    break;
                case DebugNoteTypes::FATAL:
                    $typeMark = '[fatal]';
                    break;
                default:
                    $typeMark = '[unknown]';
                    break;
            }
					
            if( !$path ){
		$path = dirname($_SERVER['SCRIPT_FILENAME'])."/log.log";
            }
            if( $clear ){
		file_put_contents($path,'');
            }
            else{
		$flags = $flags | FILE_APPEND;
            }
            $content = $datetime.$typeMark.$msg."\r\n";         
            file_put_contents($path,$content,$flags);		
            $result = true;
	}
	return $result;
    }
	
    function debug($msg, $level = DebugLevels::COMMON_DEBUG_INFO, $clear = FALSE, $path = NULL) : bool
    {
        $result = TRUE;
        if( $level <= AppSettings::debugLevel ){
            $result = makeLogNote("[$level]".$msg,DebugNoteTypes::DEBUG,$clear,$path); 
        }
        return $result;
    }
	
    function warning($msg, $clear = FALSE, $path = NULL) : bool
    {
        $result = TRUE;
        if( DebugLevels::WARNINGS <= AppSettings::debugLevel ){
            $result = makeLogNote("[1]".$msg,DebugNoteTypes::WARNING,$clear,$path); 
        }
        return $result;
    }
	
    function fatal($msg, $clear = FALSE, $path = NULL) : bool
    {
        $result = makeLogNote("[0]".$msg,DebugNoteTypes::FATAL,$clear,$path);
    	return $result;
    }
?>