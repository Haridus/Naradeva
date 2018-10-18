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
require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once('./general/stringf/generate_password.php');
require_once('./general/query/query.php');
require_once('./general/mail/mail.php');
require_once('OperationsHandlersBase.php');
require_once('./general/validate/validate.php');
require_once('./general/validate/phones.php');

//---------------------------------------------------------------------------------------------
    function changeContactData($dblink,$user,$mail = NULL,$phone = NULL)
    {
        $result = NULL;
        
        $whereClauseEntryes = array();
        if( validate_mail($mail) ){
            $whereClauseEntryes[] = " (mail LIKE '$mail') ";
        }
        if( validate_phone($phone) ){
            $phone = PhoneConverter::convert($phone);
            $whereClauseEntryes[] = " (phone LIKE '$phone') ";
        }
            
        if(count($whereClauseEntryes) == 0 ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad data","",__FILE__.__LINE__);
        }
        
        $whereClause = sprintf("WHERE %s LIMIT 1", implode("or", $whereClauseEntryes) );      
            
        $mtable = $user->dataSources[0];
        $query = $mtable->select($whereClause,array('id'));
        $stmt = prepare_and_execute($dblink, $query, array());
        if( $stmt ){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row){
                throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_LMP_ALREADY_EXISTS,"contact data already exists","",__FILE__.__LINE__);
            }
        }
        $stmt = NULL;

        $userId = $user->id;
            
        $kv = array();
        if( $mail ){
            $kv['mail'] = $mail;
        }
        if( $phone ){
            $kv['phone'] = $phone;
        }
            
        $stump = update_stump($user->data['stump'], $kv);
            
        $kv['stump'] = sql_field_string( $stump );
        if( $mail ){
            $kv['mail'] = sql_field_string($mail);
        }
        if( $phone ){
            $kv['phone'] = sql_field_string($phone);
        }
            
        $query = $mtable->update("WHERE (id = $userId) LIMIT 1",array_keys($kv), array_values($kv));
        $stmt = prepare_and_execute($dblink, $query, array());
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update contact data","",__FILE__.__LINE__);
        }
        $stmt = NULL;
           
        if( $mail ){
            $usertypeid = UserTypes::$metadata[$user->type]['id'];
            if(  !notification_mail_send_on_contact_data_change($dblink,$usertypeid,UserDataTypes::MAIL,$userId,$mail) ){
                debug("Fail to get user data after registration. Fail to send data confirmation mail.", DebugLevels::URGENT_AND_SYSTEM_MSG);
            }
        }
            
        $result = $stump;
        return $result;
    }
    
    function notification_message_make_on_want_help($user,$locale,&$subject,&$headers)
    {
        $result = NULL;
        switch ($locale)
        {
            case 'ru':
            default:
                $appName = AppSettings::appName;
                $userName = $user->data["name"];
                $subject = "Желание помогать";
                $msg = "<html>"
                      ."    <head>"
                      ."        <title>Mail test message</title>"
                      ."        <meta content=\"text/html\"; charset=\"UTF-8\" http-equiv=\"Content-Type\">"
                      ."    </head>"
                      ."    <body>"
                      ."        <p>$userName, Вы зарегистрировались в приложении $appName и хотите стать Helper'ом(помощником). Спасибо Вам большое.</p>"
                      ."        <p>Пожалуйста вышлите фотографию документа, номер которого Вы указали в профиле, после чего вскоре с Вами свяжется наш администратор, чтобы познакомиться с Вами(Мы хотим знать своих героев) и завершить вашу регистрацию.</p>"
                      ."    </body>"
                      ."</html>";
                $result = $msg;
                $headers = array();
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/text; charset=UTF-8';
                break;
        }
        return $result;
    }
    
    function changeProfileData($dblink,$user,$data)
    {
        $result = FALSE;
        $ptable = $user->dataSources[1];
        
//                    params.push("name="+_name)
//                    params.push("sname="+_sname)
//                    params.push("fname="+_fname)
//                    params.push("sex="+comboSex.currentIndex)
//                    params.push("birth="+_birth)
//                    params.push("helper_flag="+_helper_flag)
        
        $kv = array();
        $fieldsNames = $ptable->fieldsNames();
        foreach ($data as $key => $value) {
            if( in_array($key, $fieldsNames) ){
                $kv[$key] = sql_field_string($value);
            }
        }
                                
        if( count($kv) > 0 ){
            $refid = $user->id;
            $query = $ptable->update("WHERE (refid = $refid) LIMIT 1",array_keys($kv), array_values($kv));
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update profile data","",__FILE__.__LINE__);
            }
            $stmt = NULL;
            $result = TRUE;
        }
        
        if(array_key_exists("want_help", $data) ){
            $want_help = $data["want_help"];
            if($want_help > 0){
                $user->get($dblink,1);
                $subject = "";
                $headers = null;
                $msg = notification_message_make_on_want_help($user, $user->data["locale"], $subject, $headers);
                mail_notification_send($user->data["mail"], MailOutgoings::SYSTEM,$subject,$msg,$headers);
            }
        }
        
        return $result;
    }
    
    class ChangeContactDataHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CHANGE_CONTACT_DATA);
        }
        
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;            
            $this->operation->getArg(0,$usrStump);
            
            $refUser = NULL;
            if( $user->data['stump'] == $usrStump ){
                $refUser = $user;
            }
            else{
                //In this app this operation is applied only to user self
                //maybe contact data may be changed only by users ?
//                $usrData = decode_obj_stump($usrStump);
//                switch($usrData['type']){
//                    case UserTypes::ADMIN:
//                        if( $user->data['rights'] & UserRights::ADM_INFO_CHANGE ){
//                            $refUser = new Admin();
//                        }
//                        break;
//                    case UserTypes::USER:
//                        if( $user->data['rights'] & UserRights::URS_INFO_CHANGE){
//                            $refUser = new User();
//                        }
//                        break;
//                    default:
//                        throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
//                        break;
//                }
                if( !$refUser ){
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
                }
                if( !$refUser->getCoreData($dblink,$usrData['login'] ) ){
                        throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
                }
            }
            
            $this->operation->getArg(1,$mail);
            $this->operation->getArg(2,$phone);
            
            $new_stump = changeContactData($dblink, $refUser, $mail, $phone);
            if( !$new_stump ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update contact data","",__FILE__.__LINE__);
            }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,$new_stump);
            return $result;
        } 
    }
    
    class ChangeProfileDataHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CHANGE_PROFILE_DATA);
        }
        
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
                       
            $this->operation->getArg(0,$usrStump);
            
            $refUser = NULL;
            if( $user->data['stump'] == $usrStump ){
                $refUser = $user;
            }
            else{
                // //In this app this operation is applied only to user self
//                $usrData = decode_obj_stump($usrStump);
//                switch ($usrData['type']){
//                    case UserTypes::ADMIN:
//                        if( $user->data['rights'] & UserRights::ADM_INFO_CHANGE ){
//                            $refUser = new Admin();
//                        }
//                        break;
//                    case UserTypes::USER:
//                        if( $user->data['rights'] & UserRights::URS_INFO_CHANGE){
//                            $refUser = new User();
//                        }
//                        break;
//                    default:
//                        throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
//                        break;
//                }
                if( !$refUser ){
                    throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
                }
                if( !$refUser->getCoreData($dblink,$usrData['login'] ) ){
                        throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
                }
            }         
            
            parse_str(urldecode( $this->operation->args[1] ), $data);
            if( !changeProfileData($dblink, $refUser, $data) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update profile data","",__FILE__.__LINE__);
            }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,'');            
            return $result;
        }
    }
    
    class ChangeRightsHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CHANGE_RIGHTS);
        }
        
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            
            if( !( ($user->type == UserTypes::ADMIN) and ( $user->data['role'] & UserRoles::ADMIN > 0 ) ) ){
                //this operation is ment only for admins
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
            }
            
            $admin = $user;
            
            $this->operation->getArg(0,$usrStump);
            $usrData = decode_obj_stump($usrStump);
            
            $refUser = NULL;
            switch ($usrData['type']){
                case UserTypes::ADMIN:
                    if( $admin->data['rights'] & UserRights::ADM_RIGHTS_CHANGE ){
                        $refUser = new Admin();
                    }
                    break;
//                case UserTypes::USER:
//                    if( $admin->data['rights'] & UserRights::USR_RIGHTS_CHANGE){
//                        $refUser = new User();
//                    }
//                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
                    break;
            }
            
            if(!$refUser){
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
            }
                                    
            if( !$refUser->getCoreData( $dblink, $usrData['login'] ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user data","",__FILE__.__LINE__);
            }
            
            $this->operation->getArg(1,$rights);         
            //TODO: check rights change validity procedure
            
//          $kv = array();
//          $kv['rights'] = $rights;
//          $kv['stump'] = update_stump($refUser->data['stump'], $kv);
//                        
//blocked until detailing rights policy revising
//          $mtable = $refUser->dataSources[0];
//          $query = $mtable->update( array_keys($kv), array_values($kv) );
//          $stmt = prepare_and_execute($dblink, $query, array());
//          if( !$stmt){
//              throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to change rights","",__FILE__.__LINE__);
//          }
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,'');
            
            return $result;
        }
    }
 
    class ChangePasswordHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CHANGE_PASSWORD);
        }
	
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            $this->operation->getArg(0,$usrStump);
            
            $pass = NULL;
            $refUser = NULL;
            if( $user->data['stump'] == $usrStump ){
                $refUser = $user;
                $this->operation->getArg(1,$pass);
                
                if( !$pass or strlen($pass) == 0){
                    $pass = generateStrongPassword();
                }
            }
            else{
                 //In this app this operation is applied only to user self
//                $usrData = decode_obj_stump($usrStump);
//                $usrType = $usrData['type'];
//                switch($usrType){
//                    case UserTypes::ADMIN:
//                        if( !( $user->data['rights'] & UserRights::ADM_CHANGE) ){
//                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
//                        }
//                        $refUser = new Admin();
//                        break;
//                    case UserTypes::USER:
//                        if( !( $user->data['rights'] & UserRights::USR_CHANGE) ){
//                            throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
//                        }
//                        $refUser = new User();
//			break;
//                    default:
//                        throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
//			break;
//                }
                if( !$refUser){
                        throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"no rights","",__FILE__.__LINE__);
                }
                if( !$refUser->getCoreData($dblink,$usrData['login'] ) ){
                        throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
                }
                
                $pass = generateStrongPassword();
            }
		            
            $passMem = $pass;
            $pass = password_encrypt($pass);
            
            $id = $refUser->id;
            $mTable = $refUser->dataSources[0];
            $query = $mTable->update("WHERE (id = $id) LIMIT 1",array('pass'),array("'$pass'"));
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to change password","",__FILE__.__LINE__);
            }
           		
            mail_notification_send($refUser->data['mail'],MailOutgoings::SYSTEM,'password change',"You passsword was changed to: $passMem");
			
            $result = new Response($this->operation->operation,ErrorCodes::OK,"");
            return $result;
        }
    }
    
    class RestorePasswordHandler extends OperationsHandler
    {
        public function __construct($operation,$session)
	{
            parent::__construct($operation,$session, Operations::RESTORE_PASSWORD);
	}
				
        public function handleBody($dblink,$session) : Response
	{
            $result = NULL;
            
            $this->operation->getArg(0,$key);
            $this->operation->getArg(1,$lmpKey);
            
            if( $key != AppSettings::registerKey ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad restoration key","",__FILE__.__LINE__);
            }
            
            if( strlen($lmpKey) == 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad restoration data(1)","",__FILE__.__LINE__);
            }
                
            $whereClauseEntryes = array();
            if( validate_login( $lmpKey ) ){
                $login = $lmpKey;
                $whereClauseEntryes[] = "(login = '$login')";
            }
            elseif( validate_mail( $lmpKey ) ){
                $mail = $lmpKey;
                $whereClauseEntryes[] = "(mail = '$mail')";
            }
            elseif( validate_phone($lmpKey) ){
                $phone = PhoneConverter::convert($lmpKey);
                $whereClauseEntryes[] = "(phone = '$phone')";
            }
            else{
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad restotarion data(2)","",__FILE__.__LINE__);
            }
            
            if( count( $whereClauseEntryes ) == 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad restotarion data(3)","",__FILE__.__LINE__);
            }
            $whereClause = sprintf(" WHERE %s LIMIT 1 ", implode("OR", $whereClauseEntryes) );
            
            $mtable = new TableUsers();
            $query = $mtable->select($whereClause);
            $stmt = prepare_and_execute($dblink, $query);
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check user data(1)","",__FILE__.__LINE__);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = NULL;
            
            if( !$row or count($row) == 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check user data(2)","",__FILE__.__LINE__);
            }
            
            $id = $row['id'];
            $passMem = generateStrongPassword();
            $pass    = password_encrypt($passMem);
            
            $query = $mtable->update("WHERE (id = $id) LIMIT 1", array('pass'), array("'$pass'") );
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to restore password","",__FILE__.__LINE__);
            }
           		
            mail_notification_send($row['mail'],MailOutgoings::SYSTEM,'password change',"You passsword was changed to: $passMem");
			
            $result = new Response($this->operation->operation,ErrorCodes::OK,"");
            return $result;
	}		
    };

?>