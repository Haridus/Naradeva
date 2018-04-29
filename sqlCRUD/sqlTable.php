<?php
	require_once('sqlField.php');
	
	class SqlTable
	{
		public $name = "";
		public $fields = [];
		public $internalOptions = array();
		public $externalOptions = array();
		
		public function __construct($name, $fields, $internalOptions, $externalOptions)
		{
			$this->name = $name;
			for($i = 0; $i<count($fields); $i++ )
			{
				$this->fields[$fields[$i]->name] = $fields[$i];
			}			
			$this->internalOptions = $internalOptions;
			$this->externalOptions = $externalOptions;
		}
		
		public function fieldsNames()
		{
			$keys = array_keys($this->fields);
			for( $i = 0; $i< count($keys); $i++ ){
				if( $this->fields[ $keys[$i] ]->autovalue ){
					unset($keys[$i]);
				}
			}
			return array_values($keys);
		}
		
		public function placeholders($fields = NULL)
		{
			$result = FALSE;
			$keys = $this->fieldsNames();
			$placeholders = [];
			$fieldsStack = array();
			
			if( is_array($fields) ){
				foreach($fields as $fieldName ){
					if( in_array( $fieldName, $keys ) ){
						$fieldsStack[] = $fieldName;
					}
					else{
						$ok = FALSE;
						$fieldsStack = array();		
						break;
					}
				}
			}
			else{
				$fieldsStack = $keys;
			}
			
			foreach($fieldsStack as $fieldName ){
				$placeholders[] = ":".$fieldName;	
			}
			
			if( count($placeholders) > 0 ){
				$result = $placeholders;				
			}
			
			return $result;
			
		}
		
		public function create($prepedOptions = "")
		{
			$keys = array_keys($this->fields);
			$fieldsStrings = array();
			for( $i = 0;$i<count($keys);$i++){
				$fieldsStrings[] = $this->fields[ $keys[$i] ]->toString();
			} 
			$fieldsStrings = array_merge($fieldsStrings,$this->internalOptions);
			
			return sprintf("CREATE TABLE %s %s(%s)%s",$prepedOptions,$this->name,implode(",",$fieldsStrings),$this->externalOptions);
		}
		
		public function drop()
		{
			return sprintf("DROP TABLE %s",$this->name);
		}
		
		public function insert($fields=array(),$values = NULL)
		{
			$keys = array();
			$vals = array();
			
			if( ( count($fields) > 0 ) and ( is_array($values) or is_string($values) )  ){
				for( $i = 0; $i < count($fields); $i++ ){
					if( array_key_exists($fields[$i],$this->fields) ){
						$keys[] = $fields[$i];
						if( is_array($values) ){
							$vals[] = $values[$i];
						}
						elseif( is_string( $values ) ){
							$vals[] = $values;
						}
					}
				}
			}
			else{
				$keys = array_keys($this->fields);
				$vals = array_fill(0,count($keys),"?");
			}
			
			for( $i = 0; $i< count($keys); $i++ ){
				if( $this->fields[ $keys[$i] ]->autovalue ){
					unset($keys[$i]);
					unset($vals[$i]);
				}
			}
			
			return sprintf("INSERT INTO %s(%s) VALUES(%s)",$this->name,implode(",",$keys),implode(",",$vals));
		}
		
		public function delete($whereClause = "")
		{
			return sprintf("DELETE FROM %s %s",$this->name,$whereClause);
		}
		
		public function update($whereClause ="",$fields=array(),$values = NULL )
		{
			$keys = array();
			$vals = array();
			if( ( count($fields) > 0 ) and ( is_array($values) or is_string($values) )  ){
				for( $i = 0; $i < count($fields); $i++ ){
					if( array_key_exists($fields[$i],$this->fields) ){
						$keys[] = $fields[$i];
						if( is_array($values) ){
							$vals[] = $values[$i];
						}
						elseif( is_string( $values ) ){
							$vals[] = $values;
						}
					}
				}
			}
			else{
				$keys = array_keys($this->fields);
				$vals = array_fill(0,count($keys),"?");
			}
			
			for($i = 0; $i< count($keys); $i++ ){
				if( $this->fields[ $keys[$i] ]->autovalue ){
					unset($keys[$i]);
					unset($vals[$i]);
					$i--;
				}
			}
			
			$setEntryes = array();
			for($i = 0; $i < count($keys); $i++ ){
				$setEntryes[] = sprintf("%s='%s' ",$keys[$i],$vals[$i]);
			}
			return sprintf("UPDATE %s SET %s %s",$this->name,implode(",",$setEntryes),$whereClause);
		}
		
		public function select($whereClause = "", $fields = array())
		{
			$fieldsStr = "";
			$fieldsEntryes;
			if( is_array($fields) and count($fields) > 0 ){
				$fieldsEntryes = $fields;			
			}
			else{
				$fieldsEntryes = array_keys( $this->fields );
			}
			
			$fieldsStr = implode(",",$fieldsEntryes);
			
			return sprintf("SELECT %s FROM %s %s",$fieldsStr,$this->name,$whereClause);
		}
	}

?>