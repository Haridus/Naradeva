<?php
	require_once('LocalSettings.php');
	require_once('global.php');
	require_once('AppTables.php');
	require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
	require_once('PDO/Mysql.php');
	require_once('PDO/connect.php');
	require_once('operationHandler.php');
	
	class MyPDOExeption extends PDOException
	{
		public function __construct($message = null, $code = null)
		{
			$this->message = $message;
			$this->code = $code;			
		}
	}
	
	function execute_prepared_query($db, $query, $format, $args) : bool
	{
		$result = false;
		//placing $format to the first elemet in args for invokeArgs call
		$argsRef = array($format);
		for($i = 0; $i < count($args); $i++){
			$argsRef[] = &$args[$i];
		}
		//---
		
		if( ( $res = $db->prepare($query) ) ){
			$ref = new ReflectionClass('mysqli_stmt');
			if( ( $method = $ref->getMethod('bind_param') ) ){
				if( $method->invokeArgs($res,$argsRef) ){
					if( !( $result = $res->execute() ) ){
						//warning('fail execute stmt:'.$db->connect_errno.':'.$db->connect_error);
						echo 'fail execute stmt:('.$res->errno.';'.$res->error.')';
					}			
				}
				else{
					//warning('fail to invoke args:'.$db->connect_errno.':'.$db->connect_error);
					echo 'fail to invoke args:('.$res->errno.';'.$res->error.')';
				}
			}
			else{
				//warning('fail to get method:'.$db->connect_errno.':'.$db->connect_error);
				echo 'fail to get method:('.$res->errno.';'.$res->error.')';
			}
			$res->close();
		}
		else{
			//warning('fail to prepare statement:'.$db->connect_errno.':'.$db->connect_error);
			echo 'fail to prepare statement:('.$res->errno.';'.$res->error.')';
		}
		return $result;
	}
	
	function install() : bool
	{
		$result = FALSE;
		////new mysqli(AppSettings::dbDefaultHost,AppSettings::dbDefaultUser,AppSettings::dbDefaultPassword,AppSettings::dbDatabase);
		$config = getDefaultConfig();
		$adaptor = getDefaultAdaptor();
				
		try{
			$dblink =  $adaptor->connect($config) ;
			$tables = array(new TableAdmins(),
			                new TableAdminsProfiles(),
			                new TableUsers(),
			                new TableUsersProfiles(),
			                new TableObserversProfiles(),
			                new TableHelpersProfiles(),
			                new TableHelpersRequests(),
			                new TableAdmSessions(),
			                new TableUsrSessions(),
			                new TableUserDataConfirmation(),
			                new TableOperationsHistory(),
			                new TableAdmSessionsHistory(),
			                new TableUsrSessionsHistory(),
			                new TableRegions()
			                );
			                			
			$dblink->beginTransaction();
			                
			$ok = TRUE;
			for( $i = 0; $i < count( $tables ); $i++ ){
				$query = $tables[$i]->create("IF NOT EXISTS");
				debug($query);
				$retVal = $dblink->query($query);
				if( !$retVal ){
					fatal('fail to initialize data on $querySources[$i]:'.$dblink->errorCode().':'.var_dump($dblink->errorInfo()));
				}
				$ok = $ok and $retVal;
			}
			
			//-----------------
			$admTable = new TableAdmins();
					
			$type  = UserTypes::ADMIN;					
			$login = AppSettings::defaultSuperadminLogin;
			$pass  = password_hash(AppSettings::defaultSuperadminPass,PASSWORD_BCRYPT );
			$mail  = AppSettings::defaultSuperadminMail;
			$phone = AppSettings::defaultSuperadminPhone;
			$role  = UserRoles::SUPER_ADMIN;
			$rights = UserRights::SADM_STANDART;
			$stump = Encrypt(AppSettings::stumpKey ,"type=$type&login=$login&pass=$pass&mail=$mail&phone=$phone&role=$role&rights=$rights");
			$query = $admTable->insert(array('login','pass','mail','phone','role','rights','stump'),
			                           array("'$login'","'$pass'","'$mail'","'$phone'","$role","$rights","'$stump'")
			                          );
			                          
			$stmt = prepare_and_execute($dblink,$query,array());
			if( !$stmt ){
				throw new MyPDOExeption("Fail to insert superadmin record [".$stmt->errorCode."]: ".var_dump($stmt->errorInfo));
			}
			$stmt = NULL;
		
			$id = $dblink->lastInsertId();
			if( $id > 0){
				$admProfileTable = new TableAdminsProfiles();
				$query = $admProfileTable->insert(array('refid'),array($id));
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					throw new MyPDOExeption("Fail to insert superadmin data [".$stmt->errorCode."]: ".var_dump($stmt->errorInfo));
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
		return $result;
	}
	
	function uninstall()
	{
		$result = FALSE;
		////new mysqli(AppSettings::dbDefaultHost,AppSettings::dbDefaultUser,AppSettings::dbDefaultPassword,AppSettings::dbDatabase);
		$config = getDefaultConfig();                 
		$adaptor = getDefaultAdaptor();
				
		try{
			$dblink =  $adaptor->connect($config) ;
			$tables = array(new TableAdmins(),
			                new TableAdminsProfiles(),
			                new TableUsers(),
			                new TableUsersProfiles(),
			                new TableObserversProfiles(),
			                new TableHelpersProfiles(),
			                new TableHelpersRequests(),
			                new TableAdmSessions(),
			                new TableUsrSessions(),
			                );
			                
			$ok = TRUE;
			for( $i = 0; $i < count( $tables ); $i++ ){
				$query = $tables[$i]->drop();
				debug($query);
				$retVal = $dblink->query($query);
				if( !$retVal ){
					fatal('fail to initialize data on $querySources[$i]:'.$dblink->errorCode().':'.var_dump($dblink->errorInfo() ) );
				}
				$ok = $ok and $retVal;
			}
			
			$result = $ok;
		}
		catch( PDOException $e )
		{
			debug($e);
			$dblink->rollback();
		}
		return $result;
	}
	
	function install_old() : bool
	{
		$result = FALSE;
		$dblink = new mysqli(AppSettings::dbDefaultHost,AppSettings::dbDefaultUser,AppSettings::dbDefaultPassword,AppSettings::dbDatabase);
		
		if( !$dblink->connect_errno ){ 		
			$querySources = array( 'queries/createTableAdmins.sql',
		    	                   'queries/createTableAdminsProfile.sql',
		        	               'queries/createTableUsers.sql',
		            	           'queries/createTableUsersProfile.sql',
		                	       'queries/createTableSession.sql',
		                    	   'queries/createTableHelpersProfile.sql');
		
			$ok = true;
			for( $i = 0; $i < count($querySources); $i++ ){
				$query = file_get_contents($querySources[$i]);
				$retValue = $dblink->query($query);
				if( !$retValue ){
					fatal('fail to initialize data on $querySources[$i]:'.$dblink->connect_errno.':'.$dblink->connect_error);
					$ok = $ok and $retValue;
				}
			}
					
			//INSERT INTO pref_admins(mail,login,pass,phoneNumber) VALUES(?,?,?,?)		
			$query = file_get_contents("queries/insertAdmin.sql");
			if ( execute_prepared_query($dblink,$query,"ssss",array(AppSettings::defaultSuperadminMail,
			                                                        AppSettings::defaultSuperadminLogin,
			                                                        AppSettings::defaultSuperadminPass,
			                                                        AppSettings::defaultSuperadminPhone) ) ){
																		
				$id = $dblink->insert_id;
				//INSERT INTO pref_admins_profile(admin_id,name,secondName,familyName,birth,role,rights) VALUES(?,?,?,?,?,?,?) 
				$query = file_get_contents("queries/insertAdminProfile.sql");
				$ok = $ok and execute_prepared_query($dblink,$query,"dssssdd",array($id,"","","","1800-01-01",0,0xFFFFFFF));
			}
			
			$result = $ok;
		}
		return $result;
	}
	
	
		
?>