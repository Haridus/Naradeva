<?php

class SQLFieldTypes
{
    CONST INT  = 0;
    CONST DOUBLE = 1;
    CONST TEXT = 2;
    CONST TIME = 3;
    CONST DATE = 4;
    CONST DATETIME = 5;
    CONST BLOB = 6;
    
    
    static $types = array('INT' => SQLFieldTypes::INT,
                          'TINYINT' => SQLFieldTypes::INT,
                          'BIGINT'  => SQLFieldTypes::INT,
                          'DOUBLE' => SQLFieldTypes::DOUBLE,
                          'TEXT' => SQLFieldTypes::TEXT,
                          'TIME' => SQLFieldTypes::TIME,
                          'DATE' => SQLFieldTypes::DATE,
                          'DATETIME' => SQLFieldTypes::DATETIME,
                          'BLOB' => SQLFieldTypes::BLOB
    );
}

class SqlField
{
	public $name;
	public $type;
        public $typeClass;
	public $options = [];
	public $autovalue = FALSE;
	public $defaultValue = FALSE;
        public $flags = NULL;
	
	public function __construct($name, $type, $options=[])
	{
		$this->name = $name;
		$this->type = strtoupper($type);
                $this->options = array_change_key_case($options,CASE_UPPER);
		
		if( in_array("AUTO_INCREMENT",$this->options) ){
			$this->autovalue = TRUE;
		}
                if( is_integer( strpos($this->type,"VARCHAR") ) ){
                    $this->typeClass = SQLFieldTypes::TEXT;
                }
                else{
                    $this->typeClass = SQLFieldTypes::$types[$this->type];
                }
		
		for( $i = 0; $i < count( $this->options ); $i++ ){
			$defPos = strpos($this->options[$i],"DEFAULT" );
			if(  $defPos!= FALSE and $defPos == 0 ){
				$this->defaultValue = TRUE;
				break;
			}
		}
	}
	
	public function toString()
	{
		return sprintf("%s %s %s",$this->name,$this->type,implode(" ",$this->options));
	}
}

?>