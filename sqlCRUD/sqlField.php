<?php

class SqlField
{
	public $name;
	public $type;
	public $options = [];
	public $autovalue = FALSE;
	public $defaultValue = FALSE;
	
	public function __construct($name, $type, $options=[])
	{
		$this->name = $name;
		$this->type = $type;
		$this->options = array_change_key_case($options,CASE_UPPER);
		
		if( in_array("AUTO_INCREMENT",$this->options) ){
			$this->autovalue = TRUE;
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