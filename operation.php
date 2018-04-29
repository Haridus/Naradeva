<?php
	require_once('error.php');
	require_once('processingExeption.php');

//---------------------------------------------------------------------------------------------------
	class Operations
	{
		//------general--------------------
		CONST LOGIN       = "login";
		CONST _EXIT       = "exit";
		CONST LOGOUT      = "logout";
		CONST REGISTRATE    = "registrate";
		CONST CREATE_USER = "create_user";
		CONST DELETE_USER = "delete_user";
		CONST DELETE_USER_CONFIRMATION = "delete_user_confirmation";
		CONST GET_USER    = "get_user";
		CONST CHANGE_USER = "change_user";
		CONST CHANGE_PASSWORD = "change_password";
		CONST GET_USER_INFO = "get_user_info";
		CONST SET_USER_INFO = "set_user_info";
		CONST SET_USER_INFO_EX = "set_user_info_ex";
		CONST SET_USER_INFO_FIELD = "set_user_info_field";
		
		CONST GET_USERS_COUNT = "get_users_count";
		CONST GET_USERS_LIST  = "get_users_list";
		//------asking for help------------
		CONST ADD_REQUEST   = "add_request";
		CONST CHANGE_REQUEST = "change_request";
		CONST CLOSE_REQUEST = "close_request";
		CONST GET_CURRENT_REQUEST = "get_current_request";
		//------helpers--------------------
		CONST GET_REQUESTS_LIST = "get_requests_list";
		CONST TAKE_UP_CALL = "take_up_call";
		CONST GIVE_UP_CALL = "give_up_call";
		
		//----------parameters count---------------------------	
		CONST LOGIN_PARAMETERS_COUNT = 3; //login pass
		CONST EXIT_PARAMETERS_COUNT  = 0; //no
		CONST LOGOUT_PARAMETERS_COUNT  = 0; //no
		CONST REGISTRATE_PARAMETERS_COUNT = 5;//key login pass mail phone 
		CONST CREATE_USER_PARAMETERS_COUNT = 5; //type login pass mail phone
		CONST DELETE_USER_PARAMETERS_COUNT = 1; //stump
		CONST DELETE_USER_CONFIRMATION_PARAMETERS_COUNT = 1; //stump
		CONST GET_USER_PARAMETERS_COUNT = 4;//type login mail phone
		CONST CHANGE_USER_PARAMETERS_COUNT = 3; //login parameter[s] value[s]
		CONST CHANGE_PASSWORD_PARAMETERS_COUNT = 2;//stump pass
		CONST GET_USER_INFO_PARAMETERS_COUNT = 1; //stump
		CONST SET_USER_INFO_PARAMETERS_COUNT = 8;//stump mail phone name sName fName birth birth_time ?
		CONST SET_USER_INFO_EX_PARAMETERS_COUNT = 2;
		CONST SET_USER_INFO_FIELD_PARAMETERS_COUNT = 3;//stump field value
		CONST GET_USERS_COUNT_PARAMETERS_COUNT = 1;//type
		CONST GET_USERS_LIST_PARAMETERS_COUNT  = 3;//type offset count
		
		CONST ADD_REQUEST_PARAMETERS_COUNT = 7; //sh dl cats specats help_req reward text 
		CONST CHANGE_REQUEST_PARAMETERS_COUNT = 9;//stump name sh dl cats specats help_req reward text
		CONST GET_CURRENT_REQUEST_PARAMETERS_COUNT = 0; //no
		CONST CLOSE_REQUEST_PARAMETERS_COUNT = 2;//stump result 
		
		//------helpers--------------------
		CONST GET_REQUESTS_LIST_PARAMETERS_COUNT = 5;//sh dl cats specats sort
		CONST TAKE_UP_CALL_PARAMETERS_COUNT = 1;//reqstump
		CONST GIVE_UP_CALL_PARAMETERS_COUNT = 1;//reqstump
		//-------------------
		
		public static $argsValidators = [];
					
		public static $params_count = [Operations::LOGIN => Operations::LOGIN_PARAMETERS_COUNT,
		                               Operations::_EXIT => Operations::EXIT_PARAMETERS_COUNT,
		                               Operations::LOGOUT => Operations::LOGOUT_PARAMETERS_COUNT,
		                               Operations::REGISTRATE => Operations::REGISTRATE_PARAMETERS_COUNT,
									   Operations::CREATE_USER => Operations::CREATE_USER_PARAMETERS_COUNT,
									   Operations::DELETE_USER => Operations::DELETE_USER_PARAMETERS_COUNT,
									   Operations::DELETE_USER_CONFIRMATION => Operations::DELETE_USER_CONFIRMATION_PARAMETERS_COUNT,
									   Operations::GET_USER => Operations::GET_USER_PARAMETERS_COUNT,
									   Operations::GET_USER_INFO => Operations::GET_USER_INFO_PARAMETERS_COUNT,
									   Operations::SET_USER_INFO => Operations::SET_USER_INFO_PARAMETERS_COUNT,
									   Operations::SET_USER_INFO_EX => Operations::SET_USER_INFO_EX,
									   Operations::SET_USER_INFO_FIELD => Operations::SET_USER_INFO_FIELD_PARAMETERS_COUNT,
									   Operations::CHANGE_USER => Operations::CHANGE_USER_PARAMETERS_COUNT,
									   
									   Operations::GET_USERS_COUNT => Operations::GET_USERS_COUNT_PARAMETERS_COUNT,
									   Operations::GET_USERS_LIST => Operations::GET_USERS_LIST_PARAMETERS_COUNT,
									   
									   Operations::ADD_REQUEST => Operations::ADD_REQUEST_PARAMETERS_COUNT,
									   Operations::CHANGE_REQUEST => Operations::CHANGE_REQUEST_PARAMETERS_COUNT,
									   Operations::GET_CURRENT_REQUEST => Operations::GET_CURRENT_REQUEST_PARAMETERS_COUNT,
									   Operations::CLOSE_REQUEST => Operations::CLOSE_REQUEST_PARAMETERS_COUNT,
									   Operations::GET_REQUESTS_LIST => Operations::GET_REQUESTS_LIST_PARAMETERS_COUNT,
									   Operations::TAKE_UP_CALL => Operations::TAKE_UP_CALL_PARAMETERS_COUNT,
									   Operations::GIVE_UP_CALL => Operations::GIVE_UP_CALL_PARAMETERS_COUNT
									   ];	
									   
		static function initializeArgsValidators()
		{
			if( count(Operations::$argsValidators) == 0 ){
				Operations::$argsValidators[Operations::LOGIN] = function($args){return count($args) == Operations::LOGIN_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::_EXIT] = function($args){return TRUE;};
		        Operations::$argsValidators[Operations::LOGOUT] = function($args){return TRUE;};
		        
		        Operations::$argsValidators[Operations::REGISTRATE] = function($args){return count($args) == Operations::REGISTRATE_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::CREATE_USER] = function($args){return count($args) == Operations::CREATE_USER_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::DELETE_USER] = function($args){return count($args) == Operations::DELETE_USER_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::DELETE_USER_CONFIRMATION] = function($args){return FALSE;};
				Operations::$argsValidators[Operations::GET_USER] = function($args){return count($args) == Operations::GET_USER_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GET_USER_INFO] = function($args){return count($args) == Operations::GET_USER_INFO_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::SET_USER_INFO] = function($args){return count($args) == Operations::SET_USER_INFO_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::SET_USER_INFO_EX] = function($args){return count($args) == Operations::SET_USER_INFO_EX_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::SET_USER_INFO_FIELD] = function($args){return count($args) == Operations::SET_USER_INFO_FIELD_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::CHANGE_USER] = function($args){return count($args) == Operations::CHANGE_USER_PARAMETERS_COUNT;};
					
				Operations::$argsValidators[Operations::CHANGE_PASSWORD] = function($args){return count($args) == Operations::CHANGE_PASSWORD_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GET_USERS_COUNT] = function($args){return count($args) == Operations::GET_USERS_COUNT_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GET_USERS_LIST] = function($args){return count($args) == Operations::GET_USERS_LIST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::ADD_REQUEST] = function($args){return count($args) == Operations::ADD_REQUEST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::CHANGE_REQUEST] = function($args){return count($args) == Operations::CHANGE_REQUEST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GET_CURRENT_REQUEST] = function($args){return count($args) == Operations::GET_CURRENT_REQUEST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::CLOSE_REQUEST] = function($args){return count($args) == Operations::CLOSE_REQUEST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GET_REQUESTS_LIST] = function($args){return count($args) == Operations::GET_REQUESTS_LIST_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::TAKE_UP_CALL] = function($args){return count($args) == Operations::TAKE_UP_CALL_PARAMETERS_COUNT;};
				Operations::$argsValidators[Operations::GIVE_UP_CALL] = function($args){return count($args) == Operations::GIVE_UP_CALL_PARAMETERS_COUNT;};
			}
		}	
	}

//---------------------------------------------------------------------------------------
	class Operation
	{
		public $operationString;
		public $operation = "";
		public $args = [];
		public $isValid = FALSE;
	
		public function __construct($operationString)
		{
			Operations::initializeArgsValidators();
			$this->operationString = $operationString;
			$argString = "";
			$obracePos = strpos( $this->operationString,"(");
			$cobracePos = strpos( $this->operationString,")");
				
			if( $obracePos != FALSE and $cobracePos!=FALSE ){
				$this->operation = substr($this->operationString,0,$obracePos);
				$argString = substr($this->operationString,$obracePos+1,$cobracePos-($obracePos+1) );
				$this->operation = strtolower($this->operation);
				$argString = rtrim( ltrim($argString) );
				$argsVals = array();
								
				if( strlen($argString) > 0 ){
					$argsVals = explode(",",$argString);
				}
				
				//echo $argString."<br>".$this->operation."<br>".var_dump($argsVals); 
				//echo var_dump(Operations::$params_count);
				
				$ok = FALSE;
				//temporary here, because this is common code block for all operations
				for($i = 0; $i < count($argsVals); $i++){
					$this->args[$i] = $argsVals[$i];	
				}
				
				if( array_key_exists($this->operation,Operations::$params_count) ){
					if( Operations::$argsValidators[$this->operation]($this->args) ){
						$ok = TRUE;
					}
					else{
						throw new ProcessingExeption(ErrorCodes::OPERATION_PARAMETERS_MISMATCH,"bad parameters count in requested operation:".$this->operationString,$this->operation,__METHOD__);
					}
				}
				else{
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"unknown operation:".$this->operationString,$this->operation,__METHOD__);
				}
				$this->isValid = $ok;
			}		
		}
	}
?>