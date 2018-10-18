<?php
    
    class ResponceDataTypes
    {
        CONST BAD_TYPE = 0x0;
        CONST BOOLEAN  = 0x1;
        CONST DIGIT    = 0x2;
        CONST DOUBLE   = 0x3;
        CONST STRING   = 0x4;
        CONST MIXED    = 0xF;
        CONST JSON     = 0x10;
    };

    class Response {
	public $operation;
	public $retCode;
	public $retValue;
        public $dataType = ResponceDataTypes::BAD_TYPE;
		
	public function __construct($operation,$retCode,$retValue, $dataType = ResponceDataTypes::MIXED)
	{
            $this->operation = $operation;
            $this->retCode   = $retCode;
            $this->retValue  = $retValue;
            $this->dataType = $dataType;
	}
		
	public function response() : string
	{
            $operation = $this->operation;
            $retCode = $this->retCode;
            $retValue = $this->retValue;
            
            $kv = array('operation' => "$operation",
                        'retCode'   => "$retCode",
                        'retValue'  =>  $retValue);
            
            $responce = json_encode($kv);
            return $responce;
	}
    }
?>