<?php
require_once('LocalSettings.php');
require_once('global.php');
require_once('AppTables.php');
require_once('PDO/Mysql.php');
require_once('PDO/connect.php');
require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once('./general/stump/stump.php');
require_once('./general/stringf/generate_password.php');
require_once('./general/mail/mail.php');

class MyPDOExeption extends PDOException
{
    public function __construct($message = null, $code = null)
    {
	$this->message = $message;
	$this->code = $code;			
    }
}

function install() : bool
{
    $result = FALSE;
    try{
	$dblink = getDefaultConnect();
	$tables = array(new TableAdmins(),
	                new TableAdminsProfiles(),
                        new TableAdmSessions(),
                        new TableAdmSessionsHistory(),
	                
                        new TableUsers(),
	                new TableUsersProfiles(),
	                new TableUsrSessions(),
                        new TableUsrSessionsHistory(),
                        new TableUsersLocation(),
			new TableUserDataConfirmation(),
	                
                        new TableUsersRequest(),
                        new TableUsersRequestHistory(),
			
                        new TableOperationsHistory()                         
                        );
			                			
	$dblink->beginTransaction();
			                
	$ok = TRUE;
	for( $i = 0; $i < count( $tables ); $i++ ){
            $query = $tables[$i]->create("IF NOT EXISTS");
            $retVal = $dblink->query($query);
            if( !$retVal ){
                fatal('fail to initialize data on $querySources[$i]:'.$dblink->errorCode().':'.var_dump($dblink->errorInfo()));
            }
            $ok = $ok and $retVal;
	}
	
        if( !$ok ){
            throw new MyPDOExeption("Fail to create data structure [".$dblink->errorCode()."]: ".implode(",",$dblink->errorInfo()));
        }
        
	$admTable = new TableAdmins();					
	$type  = UserTypes::ADMIN;					
	$login = AppSettings::defaultSuperadminLogin;
	$pass  = password_encrypt(AppSettings::defaultSuperadminPass);
	$mail  = AppSettings::defaultSuperadminMail;
	$phone = AppSettings::defaultSuperadminPhone;
	$role  = UserRoles::SUPER_ADMIN;
	$rights = UserRights::SADM_STANDART;
        $kv = array('type'  => $type,
                    'login' => $login,
                    'pass'  => $pass,
                    'mail'  => $mail,
                    'phone' => $phone,
                    'role'  => $role,
                    'rights'=> $rights
                   );
	$stump = make_obj_stump($kv);
	$query = $admTable->insert(array('login','pass','mail','phone','role','rights','stump'),
			           array("'$login'","'$pass'","'$mail'","'$phone'","$role","$rights","'$stump'")
			          );
	$stmt = prepare_and_execute($dblink,$query,array());
	if( !$stmt ){
            throw new MyPDOExeption("Fail to insert superadmin record [".$dblink->errorCode()."]: ".implode(",",$dblink->errorInfo()));
	}
        $stmt = NULL;
                        
	$id = $dblink->lastInsertId();
	if( $id > 0){
            $admProfileTable = new TableAdminsProfiles();
            $query = $admProfileTable->insert(array('refid'),array($id));
            $stmt = prepare_and_execute($dblink,$query,array());
            if( !$stmt ){
		throw new MyPDOExeption("Fail to insert superadmin record [".$dblink->errorCode()."]: ".implode(",",$dblink->errorInfo()));
            }
            $stmt  = NULL;
	}
	else{
            throw new MyPDOExeption("Fail to insert superadmin data - no id [".__FILE__."]: ".__LINE__);
	}
			
	$dblink->commit();
        
	$result = $ok;
    }
    catch( PDOException $e )
    {
        debug($e);
	$dblink->rollback();
    }
    $dblink = NULL;
    
    $appName = AppSettings::appName;
    $baseUrl = AppSettings::baseUrl;
    $subject = "Приложение $appName успешно установлено";
    $passMem = AppSettings::defaultSuperadminPass;
    $msg = "<html>"
          ."    <head>"
          ."        <title>Успешная установка</title>"
          ."        <meta content=\"text/html\"; charset=\"UTF-8\" http-equiv=\"Content-Type\">"
          ."    </head>"
          ."    <body>"
          ."        <p>Приложение $appName успешно установлено на $baseUrl</p>"
          ."        <p>Ваш логин <b>$login</b></p>"
          ."        <p>Ваш пароль <b>$passMem</b></p>"
          ."    </body>"
          ."</html>";
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/text; charset=UTF-8';
    
    mail_notification_send($mail, MailOutgoings::SYSTEM, $subject, $msg, $headers);
    
    return $result;
}

?>