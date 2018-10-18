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

//-----------------------------------------------------------------------------------------				
    class GetUserHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_USER);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            $usrStump = NULL;
                
            $this->operation->getArg(0,$usrType);
            $this->operation->getArg(1,$login);
            $this->operation->getArg(2,$mail);
            $this->operation->getArg(3,$phone);         
            
            if( ( strlen($usrType) == 0 and 
                  strlen($login)   == 0 and
                  strlen($mail)    == 0 and
                  strlen($phone)   == 0 
                ) 
                or 
                ( $usrType == $user->type and 
                  ( ( strlen($login) > 0 and $login == $user->data['login'] ) or
                    ( strlen($mail)  > 0 and $mail  == $user->data['mail'] )  or
                    ( strlen($phone) > 0 and PhoneConverter::convert($phone) == PhoneConverter::convert( $user->data['phone'] ) ) 
                  ) 
                ) 
              ){
                //return stump of current user
                $usrStump = $user->data['stump'];
            }
            else{
                if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
                //this operation is ment only for admins
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
                }
            
                $admin = $user;
                $refUser = NULL;						
                switch($usrType){
                    case UserTypes::ADMIN:
                        if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ 
                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                        }
                        $refUser = new Admin();
                        break;
                    case UserTypes::USER:
                        if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ 
                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__); 
                        }
                        $refUser = new User();
                        break;
                    default:
                        throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user","",__FILE__.__LINE__);
                        break;
                }
                if( !$refUser ){
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
                }
                if( !$refUser->getCoreData($dblink,$login,$mail,$phone) ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user data","",__FILE__.__LINE__);
                }
                
                $usrStump = $refUser->data['stump'];
            }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,$usrStump);
            return $result;	
        }	
    }

    class GetUserInfoHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_USER_INFO);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            
            $this->operation->getArg(0,$userStump);
            
            $refUser  = NULL;
            if( $user->data['stump'] == $userStump ){
                $refUser = $user;
            }
            else{
                $userStumpData = decode_obj_stump($userStump);
                $usrType = $userStumpData['type'];					
                switch($usrType){
                    case UserTypes::ADMIN:
                        if( !($user->data['rights'] & UserRights::ADM_INFO_VIEW) ){ 
                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__); 
                        }
                        $refUser = new Admin();
                        break;
                    case UserTypes::USER:
                        if( !($user->data['rights'] & UserRights::URS_INFO_VIEW) ){ 
                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__); 
                        }
                        $refUser = new User();
                        break;
                    default:
                        throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user","",__FILE__.__LINE__);
			break;
                }
                if( !$refUser ){
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
                }
                if( !$refUser->getCoreData($dblink,$userStumpData['login'] ) ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user data","",__FILE__.__LINE__);
                }
            }
		
            if( !( $refUser->id > 0 ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user data","",__FILE__.__LINE__);
            }
	
            $refUser->get($dblink);
            $result = new Response($this->operation->operation,ErrorCodes::OK,$refUser->data);				
            return $result;	
        }
    }

    class GetUsersCountHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_USERS_COUNT);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            
            if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
                //this operation is ment only for admins
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
            }
            $admin = $user;
            
            $this->operation->getArg(0,$usrsType);		
		
            $mainTable;
            switch($usrsType){
                case UserTypes::ADMIN:
                    if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ 
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                    }
                    $mainTable = new TableAdmins();
                    break;
                case UserTypes::USER:
                    if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ 
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                    }
                    $mainTable = new TableUsers();
                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
                    break;
            }
				
            $tableName = $mainTable->name;
            $query = "SELECT COUNT(*) FROM $tableName ";
            $stmt = prepare_and_execute($dblink,$query,array());	
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get users count(1)","",__FILE__.__LINE__);
            }							
            $row = $stmt->fetch();
            if( !$row ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get users count(2)","",__FILE__.__LINE__);
            }
            $stmt = NULL;
            
            $count = $row[0];
            $result = new Response($this->operation->operation,ErrorCodes::OK,$count);				
            return $result;	
        }
    }
	
    class GetUsersListHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
           	parent::__construct($operation,$session,Operations::GET_USERS_LIST);
        }
		
        public function handleBodyEx($dblink,$session,$user) : Response
        {
            $result = NULL;
            if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
            //this operation is ment only for admins
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
            }
            $admin = $user;
            
            $this->operation->getArg(0,$usrsType);
            $this->operation->getArg(1,$offset);
            $this->operation->getArg(2,$count);
                        
            $mainTable;	
            $profileTable;
            switch($usrsType){
                case UserTypes::ADMIN:
                    if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ 
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__); 
                    }
                    $mainTable = new TableAdmins();
                    $profileTable = new TableAdminsProfiles();
                    break;
                case UserTypes::USER:
                    if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ 
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  
                    }
                    $mainTable = new TableUsers();
                    $profileTable = new TableUsersProfiles();
                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
                    break;
            }
			
            $mTableName = $mainTable->name;
            $pTableName = $profileTable->name;
            $query = "SELECT m.login as login, 
                             m.mail as mail , 
                             m.phone as phone,
                             m.role as role,
       		             m.rights as rights,
       			     m.stump as stump,
                             p.name as name,
       		             p.sName as sName,
       			     p.fName as fName,
       			     p.birth as birth
       	              FROM $mTableName as m
                      INNER JOIN $pTableName as p on m.id = p.refid
                      LIMIT $offset,$count";
            $stmt = prepare_and_execute($dblink,$query,array());	
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get users list","",__FILE__.__LINE__);
            }							
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = NULL;
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,$rows);				
            return $result;	
        }           
    }
    
    

?>