<?php
	require_once('error.php');
	require_once('processingExeption.php');
        require_once './user.php';

//---------------------------------------------------------------------------------------------------
    class OperationFlags
    {
        CONST UNSUPPORTED = 0x0;
        CONST URGENT = 0x1;
        CONST ADMIN  = 0x2;
        CONST USER   = 0x4;
        CONST HELPER = 0x8;
        CONST ALL = 0xffffff;
    }
    
    class OperationParameterFlags
    {
        CONST NO_FLAGS  = 0x0;
        CONST MANDATORY = 0x1;
    }
    
    class OperationParameterType
    {
        CONST NO_TYPE = 0x0;
        CONST TEXT    = 0x1;
        CONST MAIL    = 0x2;
        CONST PHONE   = 0x3;
        CONST DIGIT   = 0x4;
        CONST DOUBLE  = 0x5;
        CONST TIME    = 0x6;
    }
    
//--------------------------------------------------------------------------------------------
    class OperationParameter
    {
        public $name = "";
        public $type = OperationParameterType::NO_TYPE;
        public $flags = OperationParameterFlags::NO_FLAGS;
        public $defValue = NULL;
        
        public function __construct(...$metadata)//parameter metadata
        {  
            if( is_array( $metadata[0] ) ){
                $metadata = $metadata[0];
            }
            
            $mdcount    = count($metadata);
            $this->name = $metadata[0];
            $this->type = $metadata[1];
            if( $mdcount > 1 ){
                $this->flags = $metadata[2];
            }
            if( $mdcount >2 ){
                $this->defValue = $metadata[3];
            }
        }
    }
    
    class OperationValidator
    {   
        public $opMetadata = NULL;
        
        public function __construct($opMetadata = NULL)
        {
            $this->opMetadata = $opMetadata;
        }
        
        public function validate(&$args)
        {
            $result = TRUE;           
          //  echo var_dump($args);
            if( $this->opMetadata ){
                $iargs = array();
                $parameters = $this->opMetadata['parameters'];
            //    echo var_dump($parameters);
                if( count($args) > count( $parameters ) ){
                //    echo 'args count mismatch<br>';
                    $result = FALSE;
                }
                else{
                    for( $i = 0; $i < count($parameters); $i++ ){
                        $parameter = new OperationParameter( $parameters[$i] );
                        $value = NULL;

                        $index_exists = array_key_exists($i,$args);

                        if( !$index_exists or strlen( $args[$i] ) == 0 ){
                            if( ( $parameter->flags & OperationParameterFlags::MANDATORY > 0 ) ){
                                $result = FALSE;
                                break;
                            }
                            elseif( ( $parameter->defValue ) ){
                                $value = $parameter->defValue;
                            }
                        }
                        else{
                            $value = $args[$i];
                        }
                        
                    //    echo "value:".$value."<br>";

                        if( strlen( $value ) > 0 ){
                            switch ($parameter->type){
                                case OperationParameterType::NO_TYPE:
                        //            echo 'NO_TYPE<br>';
                                    break;
                                case OperationParameterType::TEXT:
                        //            echo 'TEXT<br>';
                                    break;
                                case OperationParameterType::MAIL:
                        //            echo 'MAIL<br>';
                                    $result = validate_mail($value);
                                    break;
                                case OperationParameterType::PHONE:
                        //            echo 'PHONE<br>';
                                    $value = urldecode($value);
                                    if( $value[0] != '+' ){
                                        $value = "+".$value;                                        
                                    }
                                    $result = validate_phone($value);
                                    break;
                                case OperationParameterType::DIGIT:
                        //            echo 'DIGIT<br>';
                                    if(is_numeric($value) ){
                                        $value = intval($value);
                                    }
                                    else{
                                        $result = FALSE;
                                    }
                                    break;
                                case OperationParameterType::DOUBLE:
                        //            echo 'DOUBLE<br>';
                                    if(is_numeric($value) ){
                                        $value = floatval($value);
                                    }
                                    else{
                                        $result = FALSE;
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }

                        //echo $parameter->name." ".$value." ".intval($result)."<br>";

                        if( $result == FALSE ){
                            break;
                        }
                        else{
                            $iargs[$i] = $value;
                            $iargs[$parameter->name] = $value;
                        }
                    }
                }
            }
            if( $result ){               
                $args = $iargs;                
            }
            return $result;
        }        
    }
    
//------------------------------------------------------------------------------    
    class Operations
    {
    //------general--------------------
        CONST LOGIN       = "login";  //type login pass
        CONST LOGIN_LMP   = "lmplogin";//type login pass
	CONST LOGOUT      = "logout"; //no
	CONST REGISTRATE  = "registrate";//key login pass mail phone 
//	CONST CREATE_USER = "create_user";//type login pass mail phone [deprecated]
	CONST DELETE_USER = "delete_user";//stump
	
        CONST GET_USER        = "get_user";   //type login mail phone
        CONST GET_USER_INFO   = "get_user_info";//stump
        
        CONST GET_USERS_COUNT = "get_users_count";//type
	CONST GET_USERS_LIST  = "get_users_list";//type offset count
	
        CONST CHANGE_PASSWORD     = "change_password"; //stump pass
        CONST CHANGE_CONTACT_DATA = "change_contact_data";//stump mail phone
        CONST CHANGE_PROFILE_DATA = "change_profile_data";//stump dataString
//        CONST CHANGE_RIGHTS       = "change_rights"; //stump rights [deprecated]
        
        CONST RESTORE_PASSWORD = "restore_password";//lmp
                
        CONST GET_HELPERS_CANDIDATES_COUNT = "get_helpers_candidates_count"; //no
        CONST GET_HELPERS_CANDIDATES_LIST  = "get_helpers_candidates_list";  //offset count
        CONST GET_HELPERS_IN_REGION        = "get_helpers_in_region";        //sh1 dl1 sh2 dl2 cats  
        CONST SET_HELPERS_DATA_CHECKED     = "set_helpers_data_checked";     //stump ok
        CONST ADMITT_HELPER  = "admitt_helper";//stump ok
	CONST SET_HELPER_DATA_CHECKED_AND_ADMITT = "set_helpers_data_checked_and_admitt";//stump {[checked],[admitted]} 
        
        CONST PULSE    = "pulse";      //sh dl signal
        
        CONST ADD_REQUEST   = "add_request"; //user_name user_phone sh dl cats specats help_req reward text 
	CONST CHANGE_REQUEST = "change_request";//stump?user_phone  sh dl cats specats help_req reward text
	CONST CLOSE_REQUEST = "close_request";//stump result comment
	CONST GET_REQUEST_BY_PHONE = "get_request_by_phone"; //user_phone
        CONST GET_CURRENT_REQUEST = "get_current_request"; //no
        CONST GET_REQUEST_INFO = "get_request_info";//stump
	
        CONST GET_REQUESTS_LIST = "get_requests_list";//sh dl cats specats sort
	CONST TAKE_UP_CALL = "take_up_call";//reqstump
	CONST GIVE_UP_CALL = "give_up_call";//reqstump
        
        CONST CONFIRM_USER_DATA     = "confirm_user_data";
        
//---------------------------------------------------------
	public static $argsValidators = [];
		
        public static $metadata = [ Operations::LOGIN                         =>['id' => 1,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array( array('type' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('login',OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('pass' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::LOGIN_LMP                     =>['id' => 2,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array( array('type' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('login',OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('pass' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )
                                                                                ],                                           
                                    Operations::LOGOUT                        =>['id' => 3,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array()
                                                                                ],
                                    Operations::REGISTRATE                    =>['id' => 4,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER,
                                                                                 'parameters' => array( array('key' ,OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('login',OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('pass' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('mail' ,OperationParameterType::MAIL,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('phone',OperationParameterType::PHONE,OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('locale',OperationParameterType::TEXT,OperationParameterFlags::NO_FLAGS, AppSettings::defaultLocale )    
                                                                                                      )
                                                                                ],
//                                    Operations::CREATE_USER                   =>['id' => 5, [deprecated]
//                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
//                                                                                 'parameters' => array(array('type' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('login',OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('pass' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('mail' ,OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('phone',OperationParameterType::TEXT,OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('locale',OperationParameterType::TEXT,OperationParameterFlags::NO_FLAGS, AppSettings::defaultLocale )
//                                                                                                      )
//                                                                                ],
                                    Operations::DELETE_USER                   =>['id' => 6,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(array('stump' ,OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ))
                                                                                ],
                                    Operations::GET_USER                      =>['id' => 7,
                                                                                 'flags' => OperationFlags::ADMIN,
                                                                                 'parameters' => array(array('type' ,OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('login',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('mail' ,OperationParameterType::MAIL, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('phone',OperationParameterType::PHONE, OperationParameterFlags::NO_FLAGS,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::GET_USER_INFO                 =>['id' => 8,
                                                                                 'flags' => OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('stump' ,OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ))
                                                                                ],
                                    Operations::GET_USERS_COUNT               =>['id' => 9,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(array('type' ,OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ))
                                                                                ],
                                    Operations::GET_USERS_LIST                =>['id' => 10,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array( array('type',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('offset',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                        array('count',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )
                                                                                ],					   
                                    Operations::ADD_REQUEST                   =>['id' => 11,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER, //user_name user_phone sh dl cats specats help_req reward text 
                                                                                 'parameters' => array(array('key',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY    ,NULL ),
                                                                                                       array('user_name',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY    ,NULL ),
                                                                                                       array('user_phone',OperationParameterType::PHONE, OperationParameterFlags::MANDATORY    ,NULL ),
                                                                                                       array('sh',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY    ,NULL ),
                                                                                                       array('dl',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY    ,NULL ),
                                                                                                       array('cats',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY   ,NULL ),
                                                                                                       array('specats',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS  ,NULL ),
                                                                                                       array('help_req',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS  , 1 ),
                                                                                                       array('reward',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 5 ),
                                                                                                       array('text',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS, NULL)
                                                                                                      )
                                                                                ],
                                    Operations::CHANGE_REQUEST                =>['id' => 12,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER,
                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('cast',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS   ,NULL ),
                                                                                                       array('specats',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS  ,NULL ),
                                                                                                       array('help_req',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS  , 1 ),
                                                                                                       array('reward',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 5 ),
                                                                                                       array('text',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, NULL)
                                                                                                      )
                                                                                ],
                                    Operations::GET_CURRENT_REQUEST           =>['id' => 13,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER,
                                                                                 'parameters' => array()
                                                                                ],
                                    Operations::GET_REQUEST_INFO              =>['id' => 14,
                                                                                 'flags' => OperationFlags::USER | OperationFlags::HELPER,
                                                                                 'parameters' => array(
                                                                                                       array('key',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY, NULL ),
                                                                                                       array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::CLOSE_REQUEST                 =>['id' => 15,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER,
                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT  , OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('result',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('comment',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('grade',OperationParameterType::DIGIT , OperationParameterFlags::NO_FLAGS,NULL )
                                                                                                      )
                                                                                ],
				    Operations::GET_REQUESTS_LIST             =>['id' => 16,
                                                                                 'flags' => OperationFlags::HELPER,
                                                                                 'parameters' => array(array('sh',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY  ,NULL ),
                                                                                                       array('dl',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY  ,NULL ),
                                                                                                       array('cast',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY ,NULL ),
                                                                                                       array('specats',OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                      )
                                                                                ],
                                    Operations::TAKE_UP_CALL                  =>['id' => 17,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('reqstump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ))
                                                                                ],
                                    Operations::GIVE_UP_CALL                  =>['id' => 18,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('reqstump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ))
                                                                                ],
                                    Operations::PULSE                         =>['id' => 19,
                                                                                 'flags' => OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('sh',OperationParameterType::DOUBLE, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('dl',OperationParameterType::DOUBLE, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                       array('state',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS,NULL ),
                                                                                                      )
                                                                                ],
                                    Operations::CHANGE_CONTACT_DATA           =>['id' => 20,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('mail', OperationParameterType::MAIL, OperationParameterFlags::NO_FLAGS,NULL),
                                                                                                       array('phone',OperationParameterType::PHONE, OperationParameterFlags:: NO_FLAGS ,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::CHANGE_PROFILE_DATA           =>['id' => 21,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('data_string',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )  
                                                                                ],
//                                    Operations::CHANGE_RIGHTS                 =>['id' => 22, [deprecated]
//                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
//                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
//                                                                                                       array('rights',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY,NULL )
//                                                                                                      )  
//                                                                                ],
                                    Operations::CHANGE_PASSWORD               =>['id' => 26,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('pass' ,OperationParameterType::TEXT, OperationParameterFlags::NO_FLAGS,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::RESTORE_PASSWORD              =>['id' => 27,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::USER|OperationFlags::HELPER,
                                                                                 'parameters' => array(array('key',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('lmp',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL )
                                                                                                      )
                                                                                ],
                                    Operations::GET_REQUEST_BY_PHONE          =>['id' => 27,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(array('user_phone',OperationParameterType::PHONE, OperationParameterFlags::MANDATORY,NULL ) )
                                                                                ],
                                    Operations::GET_HELPERS_CANDIDATES_COUNT  =>['id' => 28,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array()
                                                                                ],
                                    Operations::GET_HELPERS_CANDIDATES_LIST   =>['id' => 29,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array( 
                                                                                                        array('offset',OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY, 0),
                                                                                                        array('count' ,OperationParameterType::DIGIT, OperationParameterFlags::MANDATORY, 100)
                                                                                                      )
                                                                                ],
                                    Operations::GET_HELPERS_IN_REGION         =>['id' => 30, //sh1 dl1 sh2 dl2 cats 
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(
                                                                                                       array('sh1',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('dl1',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('sh2',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('dl2',OperationParameterType::DOUBLE, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                       array('cats',OperationParameterType::DOUBLE, OperationParameterFlags::NO_FLAGS, 0 )
                                                                                                      )
                                                                                ],  
                                    Operations::SET_HELPERS_DATA_CHECKED      =>['id' => 31,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(
                                                                                                      array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                      array('ok',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 1 )
                                                                                                 )
                                                                                ],
                                    Operations::ADMITT_HELPER                 =>['id' => 32,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(
                                                                                                      array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                      array('ok',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 1 )
                                                                                                 )
                                                                                ],
                               Operations::SET_HELPER_DATA_CHECKED_AND_ADMITT =>['id' => 33,
                                                                                 'flags' => OperationFlags::URGENT|OperationFlags::ADMIN,
                                                                                 'parameters' => array(
                                                                                                      array('stump',OperationParameterType::TEXT, OperationParameterFlags::MANDATORY,NULL ),
                                                                                                      array('cheched',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 1 ),
                                                                                                      array('admitt',OperationParameterType::DIGIT, OperationParameterFlags::NO_FLAGS, 1 )
                                                                                                 )
                                                                                ] 
				]; //$metadata   
        
        static function getOperationsSemantics()
        {
            $result = [];
            foreach (Operations::$metadata as $key => $value){
                $id = $value['id'];
                $argsStrEntryes = array();
                foreach ($value['parameters'] as $value) {
                    $argsStrEntryes[] = $value[0];
                }
                $argsStr = implode(",",$argsStrEntryes);
                $result[] = "$id: $key($argsStr)";
            }
            return $result;
        }
									   
        static function initializeArgsValidators()
	{
            if( count(Operations::$argsValidators) == 0 ){
                foreach (Operations::$metadata as $key => $value) {
                    Operations::$argsValidators[$key] = new OperationValidator($value);
                }
            }
	}	
    }

//---------------------------------------------------------------------------------------
    class Operation
    {
	public $operationString;
	public $operation = "";
	public $args = [];
        public $metadata = [];
        public $isValid = FALSE;
        
        public function __construct($operationString)
	{
            Operations::initializeArgsValidators();
            $this->operationString = $operationString;
            $argString = "";
            $obracePos = strpos( $this->operationString,"(");
            $cobracePos = strpos( $this->operationString,")");
			
            if( $obracePos != FALSE and $cobracePos!=FALSE ){
                //TODO: do not assing object internal data until ok is TRUE
                $operationName = strtolower( substr($this->operationString,0,$obracePos) );
                $argString = rtrim( ltrim( substr($this->operationString,$obracePos+1,$cobracePos-($obracePos+1) ) ) );
		$argsVals = array();
		if( strlen($argString) > 0 ){
                    $argsVals = explode(",",$argString);
		}
                                					
		$ok = FALSE;				
		if( array_key_exists($operationName,Operations::$argsValidators) ){
                    $this->operation = $operationName;
                    if( Operations::$argsValidators[$operationName]->validate( $argsVals ) ){
                        $this->metadata =  Operations::$metadata[$operationName];
                        $this->args = $argsVals;
                        $ok = TRUE;
                    }
                    else{
                        throw new ProcessingExeption(ErrorCodes::OPERATION_PARAMETERS_MISMATCH,"bad arguments:".$this->operationString,$this->operation,__FILE__.__LINE__);
                    }
                }
                else{
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"unknown operation:".$this->operationString,"",__FILE__.__LINE__);
                }
                $this->isValid = $ok;
            }		
        }
        
        public function getArg($index, &$ref)
        {
            if( array_key_exists($index, $this->args) ){
                $ref = $this->args[$index];
            }
        }
        
    }
?>