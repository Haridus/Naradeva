<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('operation.php');
require_once('response.php');
require_once('error.php');
require_once('PDO/connect.php');
require_once('AppTables.php');
require_once('session.php');
require_once('user.php');
require_once('processingExeption.php');
require_once('LocalSettings.php');
require_once('global.php');
require_once('./general/query/query.php');
require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once './general/ip/ip.php';

//---------------------------------------------------------------------------------
    abstract class OperationsHandler
    {
	public $operation;
	public $session;
	public $opType;
		
	public function __construct($operation, $session, $opType)
	{
            $this->operation = $operation;
            $this->session   = $session; 
            $this->opType    = $opType;
	}
		
	public function handle() : Response
	{
            $result = NULL;
            $dblink = getDefaultConnect();
            $stump  = $this->session;
            $session = new Session($stump);
			
            try{
                if( $this->operation->operation != $this->opType ){ 
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"unknown operation",$this->operation->operation,__FILE__.__LINE__);	
                }
					
                $result = $this->handleBody($dblink,$session);				
            } 
            catch (PDOException $e) {
                debug($e,0);
                if( $dblink->inTransaction() ){
                    $dblink->rollBack();
                }
                $result = new Response($this->operation->operation,ErrorCodes::OPERATION_UNKNOWN_ERROR,"unknown error");			
            }
            catch(ProcessingExeption $e){
                debug($e,0);	
		if( $dblink->inTransaction() ){
                    $dblink->rollBack();
                }
                $result = new  Response($this->operation->operation,$e->retCode,$e->msg);
            }
            catch(Exeption $e){
                debug($e,0);	
		if( $dblink->inTransaction() ){
                    $dblink->rollBack();
                }
                $result = new  Response($this->operation->operation, ErrorCodes::UNKNOWN_ERROR,"");
            }
            if( $result->retCode != ErrorCodes::OPERATION_UNSUPPORTED ){
                $id = NULL;
                $log_op = TRUE;
//               do not log pulse operations  
                if( $this->operation->operation == Operations::PULSE ){
                    $log_op = FALSE;
                }
		
                if( $log_op ){
                    if( is_array($session->data) and array_key_exists('refid',$session->data)){
                        $id = $session->data['refid'];
                    }
                                        
                    $ip = getRealUserIP();
//                    $continent = ""; reserved
//                    $country = "";
//                    $region = "";
//                    $city = "";
//                    $location_info = "";
                    $opId = $this->operation->metadata['id'];
                    $opFlags = $this->operation->metadata['flags'];
                    $operation = $this->operation->operation;
                    $args = Encrypt(AppSettings::stumpKey, implode(",",$this->operation->args) );
                    $datetime = date('Y-m-d H:i:s');			    
                    $retCode = $result->retCode;
                    $res     = $result->response();
                    

                    $kv = array('ip'        => $ip,
                                'opid'      => $opId,
                                'flags'     => $opFlags,
                                'operation' => $operation,
                                'time'      => $datetime,
                                'retCode'   => $retCode,
                                'refid'     => $id
                               );

                    $stump = make_obj_stump($kv);

                    $opsHistTable = new TableOperationsHistory();

                    $kv = array('ip'           => sql_field_string($ip),
                                'operation_id' => $opId,
                                'operation_flags' => $opFlags,
                                'operation' => sql_field_string($operation),
                                'args' => sql_field_string($args),
                                'retCode' => $retCode,
                                'result' => sql_field_string($res),
                                'stump' => sql_field_string($stump)
                                );
                    if($id){      
                        $kv['refid'] = $id;
                    }

                    $query = $opsHistTable->insert( array_keys($kv), array_values($kv) );
                    $stmt = prepare_and_execute( $dblink, $query, array() );
                    if(!$stmt){
                        $msg = sprintf("[error:%d]%s",$dblink->errorCode(),implode(",",$dblink->errorInfo()));
                        debug($msg, DebugLevels::WARNINGS);
                    }
                }
            }
            return $result;		
	}	
		
	public abstract function handleBody($dblink,$session) : Response;
    }
	
//-----------------------------------------------------------------------------------------------
    abstract class AutorazedOperationHandler extends OperationsHandler
    {		
	public function __construct($operation, $session,$opType)
	{
            parent::__construct($operation,$session,$opType);
	}
				
        public function handleBody($dblink,$session) : Response
	{
            if( !$session->isValid ){ 
                throw new ProcessingExeption(ErrorCodes::BAD_SESSION,"bad session data","",__FILE__.__LINE__); 
            }
            
            $id      = $session->data['refid'];
            $usrType = $session->data['type'];
                        
            cleanSessions($dblink,$session->table,$session->histTable,$id);			
                    
            if( !$session->getSid($dblink) ){
                throw new ProcessingExeption(ErrorCodes::SESSION_IS_CLOSED,"session expired","","");
            }
                    
            $session->update($dblink);            
            
            $user = NULL;
            switch ($usrType){
                case UserTypes::ADMIN:
                    $user = new Admin();
                    break;
                case UserTypes::USER:
                    $user = new User();
                    break;
                default :
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
                    break;
            }
            
            //set known user id
            $user->id = $id;
            //get core user info for performing operations   
            
            if( !$user->get($dblink,0) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to load user data","",__FILE__.__LINE__);
            }
                        
            $result = $this->handleBodyEx($dblink,$session,$user);
            return $result;
	}		
		
	public abstract function handleBodyEx($dblink,$session,$user) : Response;
    }
        
?>