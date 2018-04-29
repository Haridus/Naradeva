<?php
	function makeLogNote($msg, $type = 0, $clear = FALSE, $path = NULL) : bool
	{
		$result = false;
		if( $msg ){
			$datetime = date('[Y-m-d H:i:s]');
			$typeMark = '[]';
			$flags = 0;
			
			switch($type){
				case 0:
					$typeMark = '[debug]';
				break;
				case 1:
					$typeMark = '[warning]';
				break;
				case 2:
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
		
			file_put_contents($path,$datetime.$typeMark.$msg."\r\n",$flags);
			//error_log($datetime.$typeMark.$msg,-1,$path);
			
			$result = true;
		}
		return $result;
	}
	
	function debug($msg, $clear = FALSE, $path = NULL) : bool
	{
		return makeLogNote($msg,0,$clear,$path);
	}
	
	function warning($msg, $clear = FALSE, $path = NULL) : bool
	{
		return makeLogNote($msg,1,$clear,$path);
	}
	
	function fatal($msg, $clear = FALSE, $path = NULL) : bool
	{
		$result = makeLogNote($msg,2,$clear,$path);
		//die($msg);
		return $result;
	}

	function makeResponce($operation,$retCode,$retValue) : string
	{
		return '{"operation":$operation, "retCode":$retCode,"retValue":$retValue}';
	}
?>