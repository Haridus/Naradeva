<?php
    class ProcessingExeption extends Exception
    {
        public $method;
	public $request;
	public $retCode;
	public $msg;
		
	public function __construct($retCode,$msg,$request = "", $method = "")
	{
            $this->msg     = $msg;
            $this->retCode = $retCode;
            $this->method  = $method;
            $this->request = $request;
	}
		
	public function __toString()
	{
            return sprintf("[%d]{%s}%s(%s)",$this->retCode,$this->method,$this->msg,$this->request);			
	}
    }
?>