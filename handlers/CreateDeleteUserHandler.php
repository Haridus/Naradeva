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
require_once('OperationsHandlersBase.php');
require_once('./general/query/query.php');
require_once('./general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once('./general/stringf/generate_password.php');
require_once('./general/mail/mail.php');
require_once('./general/mail/mail.php');
require_once('./general/notification/notification.php');
require_once('./general/validate/validate.php');
require_once('./general/validate/phones.php');
//------------------------------------------------------------------------------
    function createUser($dblink,$mainTable,$profileTable,$utype,$login,$pass,$mail,$phone,$role,$rights, $locale = NULL /*only for this proj*/)
    {
        $result = NULL;
        if( !validate_login($login) ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_DATA,"bad login","",__FILE__.__LINE__);
        }
        if( strlen($pass) == 0 ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_DATA,"bad pass","",__FILE__.__LINE__);
        }
	if( !validate_mail($mail) or !validate_phone($phone) ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_DATA,"bad mail/phone data","",__FILE__.__LINE__);
        }
        
        $whereClauseEntries = array();
        $whereClauseEntries[] = "(login LIKE '$login')";
        if( $mail ){
            $whereClauseEntries[] = "(mail  LIKE '$mail')";
        }
        if( $phone ){
            $phone = PhoneConverter::convert($phone);
            $whereClauseEntries[] = "(phone LIKE '$phone')";
        }
        
        $whereClause = sprintf("WHERE %s LIMIT 1",implode("OR",$whereClauseEntries)); 
			
        $query = $mainTable->select($whereClause,array('id'));
        $stmt = prepare_and_execute($dblink,$query,array());
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check registration data","",__FILE__.__LINE__);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = NULL;
        if( is_array($rows) and count($rows) > 0 ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_LMP_ALREADY_EXISTS,"LMP already exists","",__FILE__.__LINE__);
        }
        
        $pass = password_encrypt($pass);
        
        $dblink->beginTransaction();

        $kv = array('type' => $utype,
                    'login'=> $login,
                    'mail' => $mail,
                    'phone'=> $phone
                    );
                    
        $stump = make_user_stump($kv);
        $query = $mainTable->insert(array('login','pass','mail','phone','role','rights','stump'),
                                    array("'$login'" ,"'$pass'" ,"'$mail'" ,"'$phone'" ,$role ,$rights ,"'$stump'" )
                                   );
        $stmt = prepare_and_execute($dblink,$query,array());
        if(!$stmt){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to create user(1)","",__FILE__.__LINE__);
        }      
        $id = $dblink->lastInsertId();
        $stmt = NULL;
	
        $kv = array('refid' => $id);
        if( $locale and strlen($locale) > 0 ){
            $kv['locale'] = sql_field_string($locale);
        }
        
        $query = $profileTable->insert( array_keys($kv), array_values($kv) );
        $stmt = prepare_and_execute($dblink,$query,array());
        if(!$stmt){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to insert user data","",__FILE__.__LINE__);
        }
        $stmt = NULL;
        
        //only for this project ------------------------------------------------
        if( $utype == UserTypes::USER ){
            $locationTable = new TableUsersLocation();
            $query = $locationTable->insert( array('refid'), array($id) );
            $stmt = prepare_and_execute( $dblink, $query, array() );
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to insert user data(3)","",__FILE__.__LINE__);
            }
            $stmt = NULL;
        }
        //----------------------------------------------------------------------
        
        $dblink->commit();
        
        //data confirmation notification
        $usertypeid = UserTypes::$metadata[$utype]['id'];
        if(  !notification_mail_send_on_contact_data_change($dblink,$usertypeid,UserDataTypes::MAIL,$id,$mail) ){
            debug("Fail to get user data after registration. Fail to send data confirmation mail.", DebugLevels::URGENT_AND_SYSTEM_MSG);
        }
        //----------------------------------------------------------
        
        $result = $stump;
        return $result;
    } 

//------------------------------------------------------------------------------
    class RegistrateUserHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::REGISTRATE);
        }
		
        public function handleBody($dblink,$session) : Response
        {
            $this->operation->getArg(0,$key);
            if( $key != AppSettings::registerKey ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_KEY,"bad registration key","",__FILE__.__LINE__);
            }
			
            $this->operation->getArg(1,$login);
            $this->operation->getArg(2,$pass);
            $this->operation->getArg(3,$mail);
            $this->operation->getArg(4,$phone);
            $this->operation->getArg(5,$locale);
                                             
            $mainTable = new TableUsers();
            $profileTable = new TableUsersProfiles();
            $locationTable = new TableUsersLocation();
            $utype = UserTypes::USER;
            $role  = UserRoles::USER;
            $rights = UserRights::USR_STANDART;
            
            $stump  = createUser($dblink, $mainTable, $profileTable, $utype, $login, $pass, $mail, $phone,$role,$rights,$locale);
            $result = new Response($this->operation->operation, ErrorCodes::OK,$stump);                            
            return $result;			
        }
    }
          
//---------------------------------------------------------------------------------------------------------	
    class CreateUserHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CREATE_USER);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            
            if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
                //this operation is ment only for admins
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
            }
            
            $admin = $user;           
            
            $role;
            $rights;
            $mainTable;
            $profileTable;
            $this->operation->getArg(0,$utype);
			
            switch( $utype ){
                case UserTypes::ADMIN:
                    if( !($admin->data['rights'] & UserRights::ADM_ADD) ){ 
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                    }
                    $mainTable = new TableAdmins();
                    $profileTable = new TableAdminsProfiles();
                    $role = UserRoles::ADMIN;
                    $rights = UserRights::ADM_STANDART;
                    break;
//                case UserTypes::USER:
//                    if( !($admin->data['rights'] & UserRights::USR_ADD) ){ 
//                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
//                    }
//                    $mainTable = new TableUsers();
//                    $profileTable = new TableUsersProfiles();
//                    $role = UserRoles::USER;
//                    $rights = UserRights::USR_STANDART;
//                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"usr type unsupported","",__FILE__.__LINE__);
                    break;
            }				
                
            $this->operation->getArg(1,$login);	
            $this->operation->getArg(2,$pass);
            $this->operation->getArg(3,$mail);
            $this->operation->getArg(4,$phone);
            $this->operation->getArg(5,$locale);
		    
            $stump = createUser($dblink, $mainTable, $profileTable, $utype, $login, $pass, $mail, $phone,$role,$rights,$locale);
            $result = new Response($this->operation->operation, ErrorCodes::OK,$stump); 
            return $result;	
        }
    }

    class DeleteUserHanler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::DELETE_USER);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            
            if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
                //this operation is ment only for admins
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
            }
            
            $admin = $user;
            $this->operation->getArg(0,$userStump);
            $userData = decode_obj_stump($userStump);
            if( !$userData['type'] ){ 
            	throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad stump data","",__FILE__.__LINE__);			
            }
            
            $mainTable;
            switch($userData['type']){
                case UserTypes::ADMIN:
                    if( !($admin->data['rights'] & UserRights::ADM_DELETE) ){
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                    }
                    $mainTable = new TableAdmins();
                    break;
//                case UserTypes::USER:
//                    if( !($admin->data['rights'] & UserRights::USR_DELETE) ){
//                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__); 
//                    }
//                    $mainTable = new TableUsers();
//                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user data","",__FILE__.__LINE__);
                    break;
            }
            			
            $usrLogin = $userData['login'];
            $query    = $mainTable->delete("WHERE (login LIKE '$usrLogin') LIMIT 1");
            $stmt     = prepare_and_execute($dblink,$query,array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to remove user","",__FILE__.__LINE__);
            }
            $rowCount = $stmt->rowCount();
            $stmt = NULL;
                
            $result = new Response($this->operation->operation,ErrorCodes::OK,$rowCount);
            return $result;
        }
    }
?>