<?php
	require_once('operation.php');
	require_once('response.php');
	require_once('error.php');
	require_once('PDO/connect.php');
	require_once('AppTables.php');
	require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
	require_once('session.php');
	require_once('user.php');
	require_once('ProcessingExeption.php');
	require_once('general/stringf/generate_password.php');
	require_once('general/mail/mail.php');
	
	function prepare_and_execute($dblink,$query,$binds)
	{
		$result = NULL;
		if( ( $stmt = $dblink->prepare($query) ) ){
			foreach( $binds as $key => $value ){
				$stmt->bindValue($key,$value);
			}
			if( ( $stmt->execute() ) ){
				$result = $stmt; 
			}
			else{
				debug( "[error:%d]%s",$stmt->errorCode(),var_dump($stmt->errorInfo()) );
			}
		}
		else{
			debug( "[error:%d]%s",$dblink->errorCode(),var_dump($dblink->errorInfo()) );
		}
		return $result;
	}

//-----------------------------------------------------------------------------------------------		
	abstract class OperationsHandler
	{
		public $operation;
		public $session;
		public $opType;
		
		public function __construct($operation, $session, $opType)
		{
			$this->operation = $operation;
			$this->session   = $session; 
			$this->opType = $opType;
		}
		
		public function handle() : Response
		{
			$result = NULL;
			$dblink = getDefaultConnect();
			$stump  = $this->session;
			$session = new Session($stump);
			
			try{
				if( $this->operation->operation != $this->opType ){ 
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"unknown operation","",__FILE__.__LINE__);	
				}
					
				$result = $this->handleBody($dblink,$session);				
			} 
			catch (PDOException $e) {
				debug($e);	
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_UNKNOWN_ERROR,"operation unknown");			
			}
			catch(ProcessingExeption $e){
				debug($e);	
				$result = new  Response($this->operation->operation,$e->retCode,$e->msg);
			}
			if( $result->retCode != ErrorCodes::OPERATION_UNSUPPORTED ){
			    $id = NULL;
			    if( is_array($session->data) and array_key_exists('refid',$session->data)){
					$id = $session->data['refid'];
				}
			    $operation = $this->operation->operation;
			    $datetime = date('Y-m-d H:i:s');			    
			    $retCode = $result->retCode;
			    $res     = $result->retValue;
			    $stump = Encrypt(AppSettings::stumpKey,"operation=$operation&time=$datetime&retCode=$retCode&result=$res&refid=$id");
			    $opsHistTable = new TableOperationsHistory();
			    $query = "";
			    if($id){
					$query = $opsHistTable->insert( array('refid','operation','retCode','result','stump'),
				                                array("$id","'$operation'","'$retCode'","'$res'","'$stump'")
				                              );
				}
				else{
					$query = $opsHistTable->insert( array('operation','retCode','result','stump'),
				                                	array("'$operation'","'$retCode'","'$res'","'$stump'")
				                              );
				}	
				$stmt = prepare_and_execute($dblink,$query,array());
//				debug($query,FALSE,"operations.log");
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
			if( !$session->isValid ){ throw new ProcessingExeption(ErrorCodes::BAD_SESSION,"bad session data","",__FILE__.__LINE__); }
			cleanSessions($dblink,$session->table,$session->histTable);			
			if( !$session->getSid($dblink) ){
				throw new ProcessingExeption(ErrorCodes::SESSION_IS_CLOSED,"session expired","","");
			}
			$session->update($dblink);
			$user = new User($dblink);	
			if( !$user->get( $session->data['type'], $session->data['refid'] ) ){throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to load user data","",__FILE__.__LINE__); }
						
			$result = $this->handleBodyEx($dblink,$session,$user);
			return $result;
		}		
		
		public abstract function handleBodyEx($dblink,$session,$user) : Response;
	}
		
//----------------------------------------------------------------------------------------------------------	
	class LoginHandler extends OperationsHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::LOGIN);
		}
		
		private function login($table,$sessionTable,$dblink,$login,$pass,$sessionHistTable=NULL)
		{
			$result="";
			$id = -1;
			cleanSessions($dblink,$sessionTable,$sessionHistTable);
			
			$query = $table->select("WHERE (login=:login)");
			$stmt = prepare_and_execute($dblink,$query,array(':login' => $login));
			if( $stmt ){
				$rows = $stmt->fetchAll();
				if( count($rows) == 1 ){
					$row = $rows[0];
					if( password_verify($pass, $row['pass'] ) ){
						$id = $row['id'];
					}
					else{//TODO: bad login password
					}		
				}
				else{//TODO: bad login password
				}
				$stmt = NULL;
			}
			
			if( $id > 0 ){				
				$sid = 0;
				$stump = "";
				$query = $sessionTable->select("WHERE refid = $id AND ctime > NOW()");
				$stmt = prepare_and_execute($dblink,$query,array());
				if( $stmt ){
					$rows = $stmt->fetchAll();
					if( count($rows) == 1 ){
						$row = $rows[0];
						$sid = $row['id'];
						$stump = $row['stump'];
						$result = $stump;
					} 
					$stmt = NULL;
				}
				
				if( $sid > 0 ){
					updateSession($dblink,$sessionTable,$sid);
				}
				else{
					$query = $sessionTable->insert($sessionTable->fieldsNames(),$sessionTable->placeholders());
					$query = str_replace(':otime','NOW()',$query);
					$query = str_replace(':ctime',"ADDTIME(NOW(),'0:15:0')",$query);					
					$userType = $sessionTable::type;
					$stump = makeStump("type=$userType&refid=$id&query=$query");
					$stmt = prepare_and_execute($dblink,$query,array(':refid' => $id,
					                                                 ':stump' => $stump
					                                                 )
					                           );
					if( $stmt ){
						$sid = $dblink->lastInsertId();
						$result = $stump;
						$stmt = NULL;
					}
				}
			}
						
			return $result;
		} 
				
		private function loginAdmin($dblink,$login,$pass)
		{
			$table = new TableAdmins();
			$sessiontable = new TableAdmSessions();
			$sessionHistTable = new TableAdmSessionsHistory();
			$result = $this->login($table,$sessiontable,$dblink,$login,$pass,$sessionHistTable);
			return $result;		
		}
		
		private function loginUser($dblink,$login,$pass)
		{
			$table = new TableUsers();
			$sessiontable = new TableUsrSessions();
			$sessionHistTable = new TableUsrSessionsHistory();
			$result = $this->login($table,$sessiontable,$dblink,$login,$pass,$sessionHistTable);
			return $result;
		}
		
		public function handleBody($dblink,$session) : Response
		{
			$result = NULL;
			$usrtype = $this->operation->args[0];
			$login = $this->operation->args[1];
			$pass  = $this->operation->args[2];
			$stump = "";
			switch($usrtype){
				case UserTypes::ADMIN:
					$stump = $this->loginAdmin($dblink,$login,$pass);
				break;
				case UserTypes::USER:
					$stump = $this->loginUser($dblink,$login,$pass);
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_USER_TYPE,"bad user type","",__FILE__.__LINE__);
				break;
			}
			if( strlen($stump) > 0 ){
				$result = new Response($this->operation->operation,ErrorCodes::OK,$stump);
			}				
			else{
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_LOGIN_BAD_LOGIN_DATA,"fail to login");
			}
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
			
			if( $ok ){
				$result = new Response($this->operation->operation,ErrorCodes::OK,"");
			}
			else{
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_FAIL,"");
			}			
			return $result;			
		}
	}
	
//----------------------------------------------------------------------------------------------------------	
	class RegistrateUserHandler extends OperationsHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::REGISTRATE);
		}
		
		public function handleBody($dblink,$session) : Response
		{
			$key = $this->operation->args[0];
			if( $key != AppSettings::registerKey ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_KEY,"bad registration key","",__FILE__.__LINE__);
			}
			
			$login = $this->operation->args[1];
			$pass = $this->operation->args[2];
			$mail = $this->operation->args[3];
			$phone = $this->operation->args[4];
			
			if( strlen( $login ) == 0 ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_DATA,"bad login","",__FILE__.__LINE__);
			}
			if( strlen($pass) == 0 ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_BAD_DATA,"bad pass","",__FILE__.__LINE__);
			}
			
			$whereClauseEntries = array();
			$whereClauseEntries[] = "(login LIKE '$login')";
			if( strlen($mail) > 0 ){
				$whereClauseEntries[] = "(mail LIKE '$mail')";
			}
			if( strlen($phone) > 0 ){
				$whereClauseEntries[] = "(phone LIKE '$phone')";
			}
			$whereClause = sprintf("WHERE %s",implode("OR",$whereClauseEntries)); 
			
			$usrTable = new TableUsers();
			$query = $usrTable->select($whereClause,array('id'));
			$stmt = prepare_and_execute($dblink,$query,array());
			if( !$stmt ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check registration data","",__FILE__.__LINE__);
			}
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if( is_array($rows) and count($rows) > 0 ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_REGISTRATE_LMP_ALREADY_EXISTS,"LMP already exists","",__FILE__.__LINE__);
			}
			
			try{
				$dblink->beginTransaction();
				
				$userType = UserTypes::USER;
				$pass = password_hash($pass,PASSWORD_BCRYPT );
				$role = UserRoles::USER;
				$rights = UserRights::USR_STANDART;
				$stump = Encrypt(AppSettings::stumpKey ,"type=$userType&login=$login&pass=$pass&mail=$mail&phone=$phone&role=$role&rights=$rights");
			
				$query = $usrTable->insert(array('login','pass','mail','phone','role','rights','stump'),
			    	                       array("'$login'" ,"'$pass'" ,"'$mail'" ,"'$phone'" ,"$role" ,"$rights" ,"'$stump'" )
			        	                  );
				$stmt = prepare_and_execute($dblink,$query,array());
				if(!$stmt){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to registrate user","",__FILE__.__LINE__);
				}
			
				$id = $dblink->lastInsertId();
				$stmt = NULL;
			
				$profileTable = new TableUsersProfiles();
				$query = $profileTable->insert(array("refid"),array("$id"));
				$stmt = prepare_and_execute($dblink,$query,array());
				if(!$stmt){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to insert user data","",__FILE__.__LINE__);
				}
				
				$dblink->commit();
				$result = new Response($this->operation->operation,ErrorCodes::OK,"$stump");
			}
			catch (PDOException $e) {
				debug($e);	
				$dblink->rollBack();
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_UNKNOWN_ERROR,"operation unknown");			
			}
			catch(ProcessingExeption $e){
				debug($e);
				$dblink->rollBack();	
				$result = new  Response($this->operation->operation,$e->retCode,$e->msg);
			}
			
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
			$cUserType = strtolower( $this->operation->args[0] );
			$mainTable;
			$profileTable;
			$role;
			$rights;
			
			switch( $cUserType ){
				case UserTypes::ADMIN:
				if( !($user->data['rights'] & UserRights::ADM_ADD) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableAdmins();
					$profileTable = new TableAdminsProfiles();
					$role = UserRoles::ADMIN;
					$rights = UserRights::ADM_STANDART;
				break;
				case UserTypes::USER:
						if( !($user->data['rights'] & UserRights::USR_ADD) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableUsers();
					$profileTable = new TableUsersProfiles();
					$role = UserRoles::USER;
					$rights = UserRights::USR_STANDART;
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"usr type unsupported","",__FILE__.__LINE__);
				break;
			}				
				
			if( !($mainTable and $profileTable) ){	throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to determine usr table","",__FILE__.__LINE__);	 }			
				
			try{					
				$dblink->beginTransaction();

		        $login = $this->operation->args[1];
		        $pass  = password_hash($this->operation->args[2],PASSWORD_BCRYPT );
		        $mail  = $this->operation->args[3];
				$phone = $this->operation->args[4];
				$stump = Encrypt(AppSettings::stumpKey ,"type=$cUserType&login=$login&pass=$pass&mail=$mail&phone=$phone&role=$role&rights=$rights");
				
				
				$query = $mainTable->insert( array('login','pass','mail','phone','role','rights'),
				                             array("'$login'","'$pass'","'$mail'","'$phone'","$role","$rights")
				                           );			
				$stmt = prepare_and_execute($dblink,$query,array() );
				if( !$stmt ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to insert user data","",__FILE__.__LINE__);
				}
				
				$id = $dblink->lastInsertId();
				$stmt = NULL;
					
				$query = $profileTable->insert(array("refid"),array("$id"));
				$stmt = prepare_and_execute($dblink,$query,array());
				if(!$stmt){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to insert user data","",__FILE__.__LINE__);
				}
				
				$dblink->commit();
				$result = new Response($this->operation->operation,ErrorCodes::OK,"$stump");
			}
			catch (PDOException $e) {
				debug($e);	
				$dblink->rollBack();
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_UNKNOWN_ERROR,"operation unknown");			
			}
			catch(ProcessingExeption $e){
				debug($e);
				$dblink->rollBack();	
				$result = new  Response($this->operation->operation,$e->retCode,$e->msg);
			}
			
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
			$userStump = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$userStump), $userData );
			if( !( is_array($userData) and count($userData) ) ){ 
				throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad stump data");			
			}
				
			$mainTable;
			$profileTable;
			switch($userData['type']){
				case UserTypes::ADMIN:
					if( !($admin->data['rights'] & UserRights::ADM_DELETE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableAdmins();
					$profileTable = new TableAdminsProfiles();
				break;
				case UserTypes::USER:
					if( !($admin->data['rights'] & UserRights::USR_DELETE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableUsers();
					$profileTable = new TableUsersProfiles();
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user","",__FILE__.__LINE__);
				break;
			}
			try{
				$dblink->beginTransaction();
				
				$usrLogin = $userData['login'];
				$query = $mainTable->select("WHERE login = '$usrLogin'", array("id"));
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user id","",__FILE__.__LINE__);
				}
				
				$rows = $stmt->fetchAll();
				if( count($rows) !=1 ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user id","",__FILE__.__LINE__);
				}
				
				$row = $rows[0];
				$usrId = $row['id'];
							
				$query = $mainTable->delete("WHERE id = $usrId ");
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to remove user","",__FILE__.__LINE__);
				}
				$rowCount = $stmt->rowCount();
				$stmt = NULL;
				
				$query = $profileTable->delete("WHERE refid = $usrId ");
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"","",__FILE__.__LINE__); 
				}
				$rowCount = $rowCount+$stmt->rowCount();
				$stmt = NULL;
				
				$dblink->commit();
				$result = new Response($this->operation->operation,ErrorCodes::OK,$rowCount/2);
			} 
			catch (PDOException $e) {
				debug($e);	
				$dblink->rollBack();
				$result = new Response($this->operation->operation,ErrorCodes::OPERATION_UNKNOWN_ERROR,"operation unknown");			
			}
			catch(ProcessingExeption $e){
				debug($e);
				$dblink->rollBack();	
				$result = new  Response($this->operation->operation,$e->retCode,$e->msg);
			}
			return $result;	
		}
	}
	
	class GetUserHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::GET_USER);
		}
		
		public function handleBodyEx($dblink,$session,$user) : Response
		{
			$result = NULL;
			
			$usrType = $this->operation->args[0];
			$login   = $this->operation->args[1];
			$mail    = $this->operation->args[2];
			$phone   = $this->operation->args[3];
											
			$mainTable;
								
			switch($usrType){
				case UserTypes::ADMIN:
					if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableAdmins();
				break;
				case UserTypes::USER:
					if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableUsers();
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user","",__FILE__.__LINE__);
				break;
			}
				
			$whereClauseEntries = [];
			if( strlen($login) > 0 ){
				$whereClauseEntries[] = "login='$login'";
			}
			if( strlen($mail) > 0 ){
				$whereClauseEntries[] = "mail='$mail'";
			}
			if( strlen($phone) > 0 ){
				$whereClauseEntries[] = "phone='$phone'";
			}
				
			if( count($whereClauseEntries) == 0 ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad parameters","",__FILE__.__LINE__);
			}
				
			$whereClause = sprintf("WHERE %s",implode("and",$whereClauseEntries));
				
			$query = $mainTable->select($whereClause);
			$stmt = prepare_and_execute($dblink,$query,array());
			if( !$stmt ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
			}
				
			$rows = $stmt->fetchAll();
			if( count($rows) == 0 ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
			}
			$row = $rows[0];
			$usrStump = $row['stump'];
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
			$userStump = $this->operation->args[0];
			$userStumpData = parse_str( Decrypt( AppSettings::stumpKey, $userStump ) );
			$refUser  = NULL;
			if( $user->data['stump'] == $userStump ){
				$refUser = $user;		
			}				
			else{
				$usrType = $userStumpData['type'];					
				switch($usrType){
					case UserTypes::ADMIN:
						if( !($user->data['rights'] & UserRights::ADM_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					case UserTypes::USER:
						if( !($user->data['rights'] & UserRights::URS_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					default:
						throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user","",__FILE__.__LINE__);
					break;
				}	
				$refUser = new User($dblink);
				$refUser->get($usrType,0,$userStumpData['login']);
			}
			
			if( !( $refUser->id > 0 ) ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user data","",__FILE__.__LINE__);
			}
			
			unset($refUser->data['id']);
			unset($refUser->data['refid']);
			unset($refUser->data['pass']);
			$json = json_encode($refUser->data);
			$result = new Response($this->operation->operation,ErrorCodes::OK,$json);				
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
			$usrsType = $this->operation->args[0];
				
			$mainTable;		
			switch($usrsType){
				case UserTypes::ADMIN:
					if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableAdmins();
				break;
				case UserTypes::USER:
					if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableUsers();
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
				break;
			}
				
			$tableName = $mainTable->name;
			$query = "SELECT COUNT(id) FROM $tableName ";
			$stmt = prepare_and_execute($dblink,$query,array());	
			if( !$stmt ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get users count","",__FILE__.__LINE__);
			}							
			$row = $stmt->fetch();
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
			$usrsType = $this->operation->args[0];
			$offset   = $this->operation->args[1];
			$count    = $this->operation->args[2];
				
			$mainTable;	
			$profileTable;
					
			switch($usrsType){
				case UserTypes::ADMIN:
					if( !($admin->data['rights'] & UserRights::ADM_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableAdmins();
					$profileTable = new TableAdminsProfiles();
				break;
				case UserTypes::USER:
					if( !($admin->data['rights'] & UserRights::URS_INFO_VIEW) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					$mainTable = new TableUsers();
					$profileTable = new TableUsersProfiles();
				break;
				default:
					throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
				break;
			}
			
			$mTableName = $mainTable->name;
			$pTableName = $profileTable->name;
			$query = "SELECT m.id,
	  						 m.login as login, 
       						 m.mail as mail , 
	   						 m.phone as phone,
       						 m.role as role,
       						 m.rights as rights,
       						 m.stump as stump,
                             p.name as name,
       						 p.sName as sName,
       						 p.fName as fName,
       						 p.birth as birth,
       						 FROM $mTableName as m
       						 INNER JOIN $pTableName as p on m.id = p.refid
       						 LIMIT $offset,$count";
			$stmt = prepare_and_execute($dblink,$query,array());	
			if( !$stmt ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get users list","",__FILE__.__LINE__);
			}							
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$json = json_encode($rows);
			$result = new Response($this->operation->operation,ErrorCodes::OK,$json);				
			return $result;	
		}	
	}
	
	class SetUserInfoHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::SET_USER_INFO);
		}
		
		public function handleBodyEx($dblink, $session, $user) : Response
		{
			$result = "";
			$usrStump = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$usrStump), $usrData );
			
			$refUser;
			if( $user->data['stump'] == $usrStump ){
				$refUser = $user;
			}
			else{
				$usrType = $usrData['type'];
				switch($usrType){
					case UserTypes::ADMIN:
						if( !($user->data['rights'] & UserRights::ADM_INFO_CHANGE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					case UserTypes::USER:
						if( !($user->data['rights'] & UserRights::URS_INFO_CHANGE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					default:
						throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
					break;
				}
				
				$refUser = new User($dblink);
				if( !$refUser->get($usrType,0,$usrData['login']) ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
				}
			}
			
			if( strlen($this->operation->args[1]) > 0 ){
				$refUser->data['mail'] = $this->operation->args[1];
			}				
			if( strlen($this->operation->args[2]) > 0 ){
				$refUser->data['phone'] = $this->operation->args[2];
			}
			if( strlen($this->operation->args[3]) > 0 ){
				$refUser->data['name'] = $this->operation->args[3];
			}
			if( strlen($this->operation->args[4]) > 0 ){
				$refUser->data['sName'] = $this->operation->args[4];
			}
			if( strlen($this->operation->args[5]) > 0 ){
				$refUser->data['fName'] = $this->operation->args[5];
			}
			if( strlen($this->operation->args[6]) > 0 ){
				$refUser->data['birth'] = $this->operation->args[6];
			}
			if( strlen($this->operation->args[7]) > 0 ){
				$refUser->data['birth_time'] = $this->operation->args[7];
			}
			if( !$refUser->updateFields() ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update user","",__FILE__.__LINE__); 
			}
			
			$result = new Response($this->operation->operation,ErrorCodes::OK,"");
			return $result;
		}
	}

	class SetUserInfoExHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::SET_USER_INFO_EX);
		}
		
		public function handleBodyEx($dblink, $session, $user) : Response
		{
			$result = NULL;
			$usrStump = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$usrStump), $usrData );
			
			$refUser;
			if( $user->data['stump'] == $usrStump ){
				$refUser = $user;
			}
			else{
				$usrType = $usrData['type'];
				switch($usrType){
					case UserTypes::ADMIN:
						if( !($user->data['rights'] & UserRights::ADM_INFO_CHANGE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					case UserTypes::USER:
						if( !($user->data['rights'] & UserRights::URS_INFO_CHANGE) ){ throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);  }
					break;
					default:
						throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user type","",__FILE__.__LINE__);
					break;
				}
				
				$refUser = new User($dblink);
				if( !$refUser->get($usrType,0,$usrData['login']) ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
				}
			}
			
			parse_str( urldecode($this->operation->args[1]), $opData );
			$refUser->data = array_merge($refUser->data,$opData);
			
			if( !$refUser->updateFields() ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update user","",__FILE__.__LINE__); 
			}
			
			$result = new Response($this->operation->operation,ErrorCodes::OK,"");
			return $result;
		}
	}
	
	class ChangeUserHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::CHANGE_USER);
		}
		
		public function handleBodyEx($dblink, $session, $user) : Response
		{
			$result = NULL;
			$usrStump = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$usrStump), $usrData );
			
			$refUser = NULL;
			if( $user->data['stump'] == $usrStump ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"you can't change you role/rights");
			}
			else{
				$usrType = $usrData['type'];
				switch($usrType){
					case UserTypes::ADMIN:
						if( !( $user->data['rights'] & UserRights::ADM_CHANGE) ){
							throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
						}
						break;
					case UserTypes::USER:
						if( !( $user->data['rights'] & UserRights::USR_CHANGE) ){
							throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
						}
						break;
					default:
						break;
				}
				$refUser = new User($dblink);
				if( !$refUser->get($usrType,0,$usrData['login']) ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
				}
			}
			
			if( $refUser ){
				$role = $this->operation->args[0];
				$rights = $this->operation->args[1];
				$changed = FALSE;
				if( ( strlen($role) > 0 ) and ( is_numeric($role) ) ){
					$refUser->data['role'] = $role;
					$changed = TRUE;
				}
				if( ( strlen($rights) > 0) and ( is_numeric($rights) ) ){
					$refUser->data['rights'] = $rights;
					$changed = TRUE;
				}
				if( $changed ){
					if( !$refUser->updateFields() ){
						throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update user","",__FILE__.__LINE__); 
					}
				}
			}
			else{
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user obj","",__FILE__.__LINE__); 
			}
			
			$result = new Response($this->operation->operation,ErrorCodes::OK,"");
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
			$usrStump = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$usrStump), $usrData );
			
			$refUser = NULL;
			$pass = NULL;
			if( $user->data['stump'] == $usrStump ){
				$refUser = $user;
				$pass = $this->operation->args[1];
			}
			else{
				$usrType = $usrData['type'];
				switch($usrType){
					case UserTypes::ADMIN:
						if( !( $user->data['rights'] & UserRights::ADM_CHANGE) ){
							throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
						}
						break;
					case UserTypes::USER:
						if( !( $user->data['rights'] & UserRights::USR_CHANGE) ){
							throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"your rights is not enough","",__FILE__.__LINE__);
						}
						break;
					default:
						break;
				}
				$refUser = new User($dblink);
				if( !$refUser->get($usrType,0,$usrData['login']) ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user","",__FILE__.__LINE__);
				}
				$pass = generateStrongPassword();
			}
			
			if( $refUser ){
				if( !$refUser->changeField('pass',$pass) ){
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to change field","",__FILE__.__LINE__); 
				}
			}
			else{
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user obj","",__FILE__.__LINE__); 
			}
			
			sendMailNotification($refUser->data['mail'],MailOutgoings::SYSTEM,'password change',"You passsword was changed to: $pass");
			
			$result = new Response($this->operation->operation,ErrorCodes::OK,"");
			return $result;
		}
	}
	
	//-----------------------------------------------------------------------------------------
	
	function normalizeSh($sh)
	{
		if($sh > 180){
			$f = intval( ($sh+180) /360.0);
			$sh = $sh - $f*360;
		}		
		elseif($sh < -180){
			$f = intval( ($sh-180) /(-360.0) );
			$sh = $sh+$f*360;
		}
		return $sh;
	}
	
	function normalizeDl($dl)
	{
		if($dl > 90){
			$f = intval( $dl/90 );
			$dl = 90 - abs($dl - $f*90);
		}		
		elseif($dl < -90){
			$f = intval( $dl/(-90.0) );
			$dl = -90 + abs($dl+$f*90);
		}
		return $dl;
	}

	function calculateRegionHandlers($sh,$dl)
	{
		$result = [];
		$sh = normalizeSh($sh);
		$dl = normalizeDl($dl);
		
		$shmod = $sh+180;		
		$dlmod = $dl+90;
			
		$sh_cell = intval( $shmod );
		$dl_cell = intval( $dlmod );
		$sh_rem = intval( ($shmod - $sh_cell)*100 );
		$dl_rem = intval( ($dlmod - $dl_cell)*100 );
			
		$spart = intval($dl_cell*360+$sh_cell);
		$fpart = intval($dl_rem*100+$sh_rem);
			
		$result = array($sh_cell,$dl_cell,$sh_rem,$dl_rem);
		return $result; 	
	}

	function calculateRegion($sh, $dl)
	{
		$result = "";
		$hdls = calculateRegionHandlers($sh,$dl);
		if( is_array($hdls) and count($hdls) > 3 ){
			$spart = intval($hdls[1]*360+$hdls[0]);
			$fpart = intval($hdls[3]*100+$hdls[2]);
			$result = sprintf("%'.05d%'.04d",$spart,$fpart);
		}
		return $result; 				
	}
	
	function calculateNearbyRegionsHandlers($sh,$dl)
	{
		$result = [];
		$result[] = calculateRegionHandlers(normalizeSh( $sh+0.015),normalizeDl( $dl+0.015) );
		$result[] = calculateRegionHandlers(normalizeSh($sh+0.015),normalizeDl($dl) );
		$result[] = calculateRegionHandlers(normalizeSh($sh+0.015),normalizeDl($dl-0.015) );
		$result[] = calculateRegionHandlers(normalizeSh($sh-0.015),normalizeDl($dl-0.015) );
		$result[] = calculateRegionHandlers(normalizeSh($sh-0.015),normalizeDl($dl) );
		$result[] = calculateRegionHandlers(normalizeSh($sh-0.015),normalizeDl($dl+0.015) );
		$result[] = calculateRegionHandlers(normalizeSh($sh),normalizeDl($dl-0.015) );
		$result[] = calculateRegionHandlers(normalizeSh($sh),normalizeDl($dl+0.015) );
		return $result;		
	}

	//-----------------------------------------------------------------------------------------
	
	class AddRequestHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::ADD_REQUEST);
		}
		
		public function handleBodyEx($dblink, $session, $user) : Response
		{
			$result = NULL;
			if( $user->type == UserTypes::ADMIN OR
			    !($user->data['rights'] & UserRights::SELF_REQUEST_ADD) ){
			    	throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you can't add requests: no rights","",__FILE__.__LINE__);  			
			}
			
			$refid   = $user->id;
			$sh      = $this->operation->args[0];
			$dl      = $this->operation->args[1];
			$cats    = $this->operation->args[2];
			$specats = $this->operation->args[3];
			$helpersRequested = $this->operation->args[4];
			$reward  = $this->operation->args[5];
			$text    = urldecode($this->operation->args[6]);
			
			$helpersSigned = 0;
			$helpers='';
			$status = RequestStatus::OPENED;
			$region = calculateRegion($sh,$dl);
			
			$stump = "region=$region&$sh&dl=$dl&cats=$cats&specats=$specats&hr=$helpersRequested&reward=$reward";
			$stump = Encrypt(AppSettings::stumpKey,$stump);
							
			$reqRegionTable = new TableRegionRequest($region);
			$query = $reqRegionTable->create("IF NOT EXISTS");
			$stmt = prepare_and_execute($dblink,$query,array());
			if(!$stmt){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get request table","",__FILE__.__LINE__);
			}		
			
			$tableName = $reqRegionTable->name;
			$query = "INSERT INTO $tableName(refid,otime,ctime,stump,sh,dl,cats,specats,helpers_req,helpers_signed,status,reward,text) VALUES($refid,NOW(),ADDTIME(NOW(),'0:30:0'),'$stump',$sh,$dl,$cats,'$specats',$helpersRequested,$helpersSigned,$status,$reward,'$text')";
			$stmt = prepare_and_execute($dblink,$query,array());
			if( !$stmt ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to add request","",__FILE__.__LINE__);
			}			
									
			$result = new Response($this->operation->operation,ErrorCodes::OK,$stump);
			return $result;
		}
	}
	
	class CloseRequestHandler extends AutorazedOperationHandler
	{
		public function __construct($operation, $session)
		{
			parent::__construct($operation,$session,Operations::CLOSE_REQUEST);
		}
		
		public function handleBodyEx($dblink, $session, $user) : Response
		{
			$result = NULL;
			if(  ($user->type == UserTypes::ADMIN and
			      !($user->data['rights'] & UserRights::URS_REQUEST_DELETE) ) or
			     ($user->type == UserTypes::USER and 
			      !($user->data['rights'] & UserRights::SELF_REQUEST_DELETE) ) ){
			    	throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you can't remove requests: no rights","",__FILE__.__LINE__);  			
			}
			
			$stump      = $this->operation->args[0];
			parse_str( Decrypt(AppSettings::stumpKey,$stump),$reqData );
			if( !( is_array($reqData) and count($reqData)>0 and array_key_exists('region',$reqData) ) ){
				throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad stump data","",__FILE__.__LINE__);
			}
			
			$region = $reqData['region'];
										
			$reqRegionTable = new TableRegionRequest($region);
			$query = $reqRegionTable->delete("WHERE stump LIKE '$stump'");
			$stmt = prepare_and_execute($dblink,$query,array());
			if(!$stmt){
				throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to remove request","",__FILE__.__LINE__);
			}		
											
			$result = new Response($this->operation->operation,ErrorCodes::OK,$stmt->rowCount());
			return $result;
		}
	}

	class OperationsHandlersFactory
	{
		static public $handlers = [Operations::LOGIN => "LoginHandler",
		                           Operations::_EXIT => "LogoutHandler",
		                           Operations::LOGOUT => "LogoutHandler",
		                           Operations::REGISTRATE => "RegistrateUserHandler",
								   Operations::CREATE_USER => "CreateUserHandler",
								   Operations::DELETE_USER => "DeleteUserHanler",
								   Operations::DELETE_USER_CONFIRMATION => "",
								   Operations::GET_USER => "GetUserHandler",
								   Operations::CHANGE_USER => "ChangeUserHandler",
								   Operations::CHANGE_PASSWORD => "ChangePasswordHandler",
								   Operations::GET_USER_INFO => "GetUserInfoHandler",
								   Operations::SET_USER_INFO => "SetUserInfoHandler",
								   Operations::SET_USER_INFO_EX => "SetUserInfoExHandler",
								   Operations::GET_USERS_COUNT => "GetUsersCountHandler",
								   Operations::GET_USERS_LIST  => "GetUsersListHandler",
								   Operations::ADD_REQUEST => "AddRequestHandler",
								   Operations::CHANGE_REQUEST => "",
								   Operations::GET_CURRENT_REQUEST => "",
								   Operations::CLOSE_REQUEST => "CloseRequestHandler",
								   Operations::GET_REQUESTS_LIST => "",
								   Operations::TAKE_UP_CALL => "",
								   Operations::GIVE_UP_CALL => ""
								   ];
		
	}

?>