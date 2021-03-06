<?php
    require_once('LocalSettings.php');
    require_once('operation.php');
	
    class Request {
	public $session = "";
	public $operationString = "";
	public $operation = NULL;
	public $fullRequest = "";
	public $isValid = false;
		
	public function __construct()
	{
            $this->fullRequest = file_get_contents('php://input');			
            if( array_key_exists("appkey",$_REQUEST) ){
		$correctAppkey = $_REQUEST["appkey"] == AppSettings::appkey;
		if( $correctAppkey ){
                    if( array_key_exists("session",$_REQUEST) ){
			$this->session = $_REQUEST["session"];
                    } 
                    if( array_key_exists( "operation", $_REQUEST ) ){
			$this->operationString = $_REQUEST["operation"];
			$this->operation = new Operation($this->operationString);
			$this->isValid = $this->operation->isValid;
                    }					
		}
                else{
                    throw new ProcessingExeption(ErrorCodes::REQUEST_INVALID,"bad appkey","");
                }
            }
            else{
                throw new ProcessingExeption(ErrorCodes::REQUEST_INVALID,"invalid request: ".$this->fullRequest,"");
            }
	}
    }	
?>