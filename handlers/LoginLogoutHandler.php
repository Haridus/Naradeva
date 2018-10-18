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
require_once('OperationsHandlersBase.php');
require_once('./general/validate/validate.php');
require_once('./general/validate/phones.php');
require_once('./general/validate/validate.php');
require_once('./general/validate/phones.php');

//-----------------------------------------------------------------------------------------------
    function login($dblink,$table,$sessionTable,$lmp,$pass,$sessionHistTable=NULL)
    {
        $result = NULL;
        
        if( count( $lmp ) == 0 or strlen($pass) == 0){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"no login data(1)","",__FILE__.__LINE__);
        }
        
        $whereClauseEntryes = array();
        if( array_key_exists('login', $lmp) ){
            $login = $lmp['login'];
            //may be validate
            $whereClauseEntryes[] = " (login LIKE '$login') ";
        }
        if( array_key_exists('mail', $lmp) ){
            $mail = $lmp['mail'];
            //may be validate
            $whereClauseEntryes[] = " (mail LIKE '$mail') ";
        }
        if( array_key_exists('phone', $lmp) ){
            $phone = $lmp['phone'];
            //may be validate
            $phone = PhoneConverter::convert($phone);
            $whereClauseEntryes[] = " (phone LIKE '$phone') ";
        }
        
        if( count( $whereClauseEntryes ) == 0 ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"no login data(2)","",__FILE__.__LINE__);
        }
        $whereClause = sprintf("WHERE %s LIMIT 1", implode("OR", $whereClauseEntryes) );       
        
        $query = $table->select($whereClause);
        $stmt  = prepare_and_execute($dblink,$query,array());
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check login data(1)","",__FILE__.__LINE__);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = NULL;
            
        if( !$row or count($row) == 0){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check login data(2)","",__FILE__.__LINE__);
        }
        
        $id = -1;
        if( password_verify($pass, $row['pass'] ) ){
            $id = $row['id'];
        }
           		
        if( !($id > 0) ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user by login data","",__FILE__.__LINE__);
        }
            
        cleanSessions($dblink,$sessionTable,$sessionHistTable,$id);
        
        $query = $table->update("WHERE (id = $id) LIMIT 1",array('last_visit'),array("NOW()"));
        $stmt = prepare_and_execute($dblink, $query, array());
        $stmt = NULL;
            
        $sid = 0;
        $stump = "";
        $query = $sessionTable->select("WHERE (refid = $id) AND ctime > NOW() LIMIT 1");
        $stmt = prepare_and_execute($dblink,$query,array());
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check session data(1)","",__FILE__.__LINE__);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = NULL;
                        
        if( $row ){
            $sid   = $row['id'];
            $stump = $row['stump'];
            $result = $stump;
            updateSession($dblink,$sessionTable,$sid);
        }
        else{
            //session open 
            $result = openSession($dblink, $sessionTable, $id);
        }			
        
        return $result;
    }

//-----------------------------------------------------------------------------------------------
    class LoginHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
	{
            parent::__construct($operation,$session,Operations::LOGIN);
        }
		
	public function handleBody($dblink,$session) : Response
	{
            $result = NULL;
            $this->operation->getArg(0,$usrtype);
            $this->operation->getArg(1,$lmpKey);
            $this->operation->getArg(2,$pass);
            $stump = NULL;
           
            if( !validate_login($lmpKey) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad login data","",__FILE__.__LINE__);
            }
            
            $mtable = NULL;
            $sessionTable = NULL;
            $sessionHistTable = NULL;
            switch($usrtype){
                case UserTypes::ADMIN:
                    $mtable = new TableAdmins();
                    $sessionTable = new TableAdmSessions();
                    $sessionHistTable = new TableAdmSessionsHistory();
                    break;
                case UserTypes::USER:
                    $mtable = new TableUsers();
                    $sessionTable = new TableUsrSessions();
                    $sessionHistTable = new TableUsrSessionsHistory();
                    break;
		default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
                    break;
            }
            
            $lmp = array( 'login' => $lmpKey );           
          
            $stump = login($dblink, $mtable, $sessionTable, $lmp, $pass, $sessionHistTable);
                
            if( strlen($stump) == 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_LOGIN_BAD_LOGIN_DATA,"fail to login","",__FILE__.__LINE__);
            }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,$stump);
            return $result;
        }
    }
    
//------------------------------------------------------------------------------    
    class LMPLoginHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::LOGIN_LMP);
        }
		
        public function handleBody($dblink,$session) : Response
        {
            $result = NULL;
            $this->operation->getArg(0,$usrtype);
            $this->operation->getArg(1,$lmpKey);
            $this->operation->getArg(2,$pass);
            $stump = NULL;
           
            $lmp = array();           
            if( validate_login( $lmpKey ) ){
                $lmp['login'] = $lmpKey;
            }
            elseif( validate_mail( $lmpKey ) ){
                $lmp['mail'] = $lmpKey;
            }
            elseif( validate_phone($lmpKey) ){
                $lmp['phone'] = PhoneConverter::convert($lmpKey);
            }
            else{
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad login data","",__FILE__.__LINE__);
            }
            
            $mtable = NULL;
            $sessionTable = NULL;
            $sessionHistTable = NULL;
            switch($usrtype){
                case UserTypes::ADMIN:
                    $mtable = new TableAdmins();
                    $sessionTable = new TableAdmSessions();
                    $sessionHistTable = new TableAdmSessionsHistory();
                    break;
                case UserTypes::USER:
                    $mtable = new TableUsers();
                    $sessionTable = new TableUsrSessions();
                    $sessionHistTable = new TableUsrSessionsHistory();
                    break;
		default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
                    break;
            }
                        
            $stump = login($dblink, $mtable, $sessionTable, $lmp, $pass, $sessionHistTable);
                
            if( strlen($stump) == 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_LOGIN_BAD_LOGIN_DATA,"fail to login","",__FILE__.__LINE__);
            }
                
            $result = new Response($this->operation->operation,ErrorCodes::OK,$stump);
            return $result;					
        }
    }
//-----------------------------------------------------------------------------------------------------------------	
    class LogoutHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::LOGOUT);
        }
		
        public function handleBody($dblink,$session) : Response
        {
            if( !$session->isValid ){
                throw new ProcessingExeption(ErrorCodes::SESSION_BAD_DATA,"bad session data","","");
            }
			
            cleanSessions($dblink,$session->table,$session->histTable);
	
            if( !$session->getSid($dblink) ){
                throw new ProcessingExeption(ErrorCodes::SESSION_UNKNOWN_ERROR,"fail to get sid","","");
            }
			
            $ok = $session->close($dblink);
		
            if( !$ok ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to close session","","");
            }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,"");
            return $result;			
        }
    }
?>
