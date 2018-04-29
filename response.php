<?php
	class Response {
		public $operation;
		public $retCode;
		public $retValue;
		
		public function __construct($operation,$retCode,$retValue)
		{
			$this->operation = $operation;
			$this->retCode   = $retCode;
			$this->retValue  = $retValue;
		}
		
		public function response() : string
		{
			$operation = $this->operation;
			$retCode = $this->retCode;
			$retValue = $this->retValue;
			return "{\"operation\":\"$operation\", \"retCode\":\"$retCode\",\"retValue\":\"$retValue\"}";
		}
	}
?>