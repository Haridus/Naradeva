<?php
	require_once('AppTables.php');
	require_once('PDO/connect.php');
		
	class User
	{
		public $dblink;
		public $id = 0; 
		public $type = "";
		
		public $data=[];
		/*
		public $email;
		public $login;
		public $phoneNumber;
		
		public $name;
		public $secondName;
		public $familyName;
		public $birth;
		public $male;
		public $role;
		public $rights;
		public $categories;	
		*/
		
		function __construct($dblink = NULL){
			$this->dblink = $dblink;
			//NOTHING TO DO	
		}
		
		public function get($type,$id,$login="",$mail="",$phone="")
		{
			$result = FALSE;
			$mainTable;
			$profileTable;
			if( $type == UserTypes::ADMIN ){
				$mainTable = new TableAdmins();
				$profileTable = new TableAdminsProfiles();
			}
			elseif( $type == UserTypes::USER){
				$mainTable = new TableUsers();
				$profileTable = new TableUsersProfiles();
			}
			
			if( $mainTable and $profileTable ){
				$ok = TRUE;
				$dblink = $this->dblink;
				
				$whereClauseEntryes = array();
				if( $id > 0 ){
					$whereClauseEntryes[] = "id=$id";
				}
				if( strlen($login) > 0 ){
					$whereClauseEntryes[] = "login LIKE '$login'";
				}
				if( strlen($mail) > 0 ){
					$whereClauseEntryes[] = "mail LIKE '$mail'";
				}
				if( strlen($phone) > 0 ){
					$whereClauseEntryes[] = "phone LIKE '$phone'";
				}
				$whereClause = sprintf("WHERE %s",implode("and",$whereClauseEntryes));
				
				$query = $mainTable->select($whereClause);
				$stmt = prepare_and_execute($dblink,$query,array());
				if( $stmt ){
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					if( count($rows) == 1 ){
						$row = $rows[0];
						$this->data = array_merge($this->data,$row);
					}
					else{						//TODO: more than 1 row with same id, something wrong
						$ok = $ok and FALSE;
					}
					$stmt = NULL;
				}
				else{
					$ok = $ok and FALSE;
				}
				
				if( !($id > 0) ){
					$id = $this->data['id'];
				}
				
				$query = $profileTable->select("WHERE refid=$id");
				$stmt  = prepare_and_execute($dblink,$query,array());
				if( $stmt ){
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					if( count($rows) == 1 ){
						$row = $rows[0];
						$this->data = array_merge($this->data,$row);
					}
					else{ //TODO: more than 1 row with same id, something wrong
						$ok = $ok and FALSE;
					}
				}
				else{
					$ok = $ok and FALSE;
				}
				
				if( $this->data['role'] & UserRoles::OBSERVER ){
					$observerTable = new TableObserversProfiles();
					$query = $observerTable->select("WHERE refid = $id ");
					
					$stmt  = prepare_and_execute($dblink,$query,array());
					if( $stmt ){
						$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
						if( count($rows) == 1 ){
							$row = $rows[0];
							$this->data = array_merge($this->data,$row);
						}
					}
				}
				if( $this->data['role'] & UserRoles::HELPER ){
					$helperTable = new TableHelpersProfiles();
					$query = $helperTable->select("WHERE refid = $id ");
					
					$stmt  = prepare_and_execute($dblink,$query,array());
					if( $stmt ){
						$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
						if( count($rows) == 1 ){
							$row = $rows[0];
							$this->data = array_merge($this->data,$row);
						}
					}
				}
				
				if( $ok ){
					$this->id = $id;
					$this->type = $type;
					$result = $ok;
				}
				else{
					$this->id = 0;
					$this->type="";
					$this->data = array();
				}
			}
			return $result;		
		}
		
		public function changeField($field,$value)
		{
			$result = FALSE;
			if( $this->id > 0 and in_array($field,$this->data) ){
				$mainTable;
				$profileTable;
				if( $this->type == UserTypes::ADMIN ){
					$mainTable = new TableAdmins();
					$profileTable = new TableAdminsProfiles();
				}
				elseif( $this->type == UserTypes::USER){
					$mainTable = new TableUsers();
					$profileTable = new TableUsersProfiles();
				}
				
				if( in_array( $field, array('pass','mail','phone','role','rights') ) ){	
					$pass;
	            	if($field == 'pass'){
						$pass  = password_hash($value,PASSWORD_BCRYPT );
						$value = $pass;
					}
					
					$this->data[$field] = $value;
					
					$type  =  $this->type;
					$login =  $this->data['login'];
	            	$mail  =  $this->data['mail'];
					$phone =  $this->data['phone'];
					$role  =  $this->data['role'];
					$rights = $this->data['rights'];
					
					$stump = Encrypt(AppSettings::stumpKey ,"type=$type&login=$login&pass=$pass&mail=$mail&phone=$phone&role=$role&rights=$rights");
					
					$dblink = $this->dblink;
					$query = $mainTable->update( array($field,'stump'), array($value,"$stump") );
					$stmt = prepare_and_execute($dblink,$query,array());
					if( $stmt ){
						$result = $stmt->rowCount() > 0;							
					}
				}	
				elseif( in_array($field,array('name','sName','fName','birth','birth_time') ) ){
					$this->data[$field] = $value;
					$dblink = $this->dblink;
					$query = $profileTable->update( array($field), array($value) );
					$stmt = prepare_and_execute($dblink,$query,array());
					if( $stmt ){
						$result = $stmt->rowCount() > 0;							
					}					
				}							
			}
			return $result;	
		}
		
		public function mergeUpdate($upData)
		{
			$result = false;
			return $result;
		}
		
		public function updateFields()
		{
			$result = FALSE; 
			if( $this->id > 0 ){
				$mainTable;
				$profileTable;
				$dblink = $this->dblink;
				$dblink->beginTransaction();
				
				if( $this->type == UserTypes::ADMIN ){
					$mainTable = new TableAdmins();
					$profileTable = new TableAdminsProfiles();
				}
				elseif( $this->type == UserTypes::USER){
					$mainTable = new TableUsers();
					$profileTable = new TableUsersProfiles();
				}
				
				$passInfo = password_get_info( $this->data['pass'] );
				if( $passInfo['algoName'] != 'bcrypt' and strlen($this->data['pass']) > 0 ){ //defValue
					//$pass  changed need to update
					$this->data['pass'] = password_hash($this->data['pass'],PASSWORD_BCRYPT );
				}
					
				$id = $this->id;
				$type =  $this->type;
				$login = $this->data['login'];
	            $pass  = $this->data['pass'];
	            $mail  = $this->data['mail'];
				$phone = $this->data['phone'];
				$role  =  $this->data['role'];
				$rights = $this->data['rights'];
					
				$stump = Encrypt(AppSettings::stumpKey ,"type=$type&login=$login&pass=$pass&mail=$mail&phone=$phone&role=$role&rights=$rights");
					
				$query = $mainTable->update("WHERE id=$id ",
				                            array('pass','mail','phone','role','rights','stump'), 
				                            array($pass,$mail,$phone,$role,$rights,$stump));
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					$dblink->rollBack();
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update fields ".__FILE__.__LINE__,"","");	
				}
				
				$name = $this->data['name'];
				$sName = $this->data['sName'];
				$fName = $this->data['fName'];
				$birth = $this->data['birth'];
				$birth_time = $this->data['birth_time'];
						
				$query = $profileTable->update("WHERE refid=$id",
				                               array('name','sName','fName','birth','birth_time'),
				                               array($name,$sName,$fName,$birth,$birth_time));
				                               
				$stmt = prepare_and_execute($dblink,$query,array());
				if( !$stmt ){
					$dblink->rollBack();
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update fields ","","");	
				}
				$dblink->commit();
				$result = TRUE;	
			}									
			return $result;
		}
	}
?>