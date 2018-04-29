<?php
	require_once('LocalSettings.php');
	require_once('AppTables.php');
	require_once('PDO/connect.php');

	function checkSession($dblink,$sessionTable,$stump)
	{
		$result = NULL;
		$query = $sessionTable->select("WHERE stump LIKE '$stump'");
		$stmt = prepare_and_execute($dblink,$query,array());
		if( $stmt ){
			$result = $stmt->fetchAll();
		}
		$stmt = NULL;
		return $result;
	}
	
	function cleanSessions($dblink, $sessionTable, $histTable = NULL)
	{
		$result = 0;
		if( $histTable ){
			$query = $sessionTable->select("WHERE ctime <= NOW()");
			$stmt = prepare_and_execute($dblink,$query,array());
			if( $stmt ){
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				for($i =0; $i<count($rows); $i++){
					$row = $rows[$i];
					$id = $row['id'];
					$refid = $row['refid'];
					$otime = $row['otime'];
					$ctime = $row['ctime'];
					$stump = $row['stump'];
					$status = SessionStatus::EXPIRED;
				
					$query = $histTable->insert( array('id','refid','otime','ctime','stump','status'),
											     array("$id","$refid","'$otime'","'$ctime'","'$stump'","$status")
											   );
					$stmt = prepare_and_execute($dblink,$query,array());
				}
			}
			$stmt = NULL;
		}
		
		$query = $sessionTable->delete("WHERE ctime <= NOW()");
		$stmt = prepare_and_execute($dblink,$query,array());
		if($stmt){
			$result = $stmt->rowCount();
		}
		$stmt = NULL;
		return $result;
	}
	
	function updateSession($dblink,$sessionTable,$sid)
	{
		$result = FALSE;
		$table = $sessionTable->name;
		$query = "UPDATE $table SET ctime=ADDTIME(NOW(),'0:15:0') WHERE id=$sid";
		$stmt = prepare_and_execute($dblink,$query,array());
		if( $stmt ){
			$result = TRUE;										
		}
		$stmt = NULL;
		return $result;
	}
	
	function deleteSession($dblink,$sessionTable,$sid,$histTable = NULL)
	{
		$result = FALSE;
		if( $histTable ){
			$query = $sessionTable->select("WHERE id=$sid");
			$stmt = prepare_and_execute($dblink,$query,array());
			if( $stmt ){
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				for($i =0; $i<count($rows); $i++){
					$row = $rows[$i];
					$id = $row['id'];
					$refid = $row['refid'];
					$otime = $row['otime'];
					$ctime = $row['ctime'];
					$stump = $row['stump'];
					$status = SessionStatus::CLOSED;
				
					$query = $histTable->insert( array('id','refid','otime','ctime','stump','status'),
											     array("$id","$refid","'$otime'","NOW()","'$stump'","$status")
											   );
					$stmt = prepare_and_execute($dblink,$query,array());
				}
			}
			$stmt = NULL;
		}
		
		$query = $sessionTable->delete("WHERE id=$sid");
		$stmt = prepare_and_execute($dblink,$query,array());
		if( $stmt ){
			$result = TRUE;										
		}
		$stmt = NULL;
		return $result;
	}
	
	function makeStump($stumpSeed)
	{
		$stump=Encrypt(AppSettings::stumpKey, $stumpSeed);
		return $stump;
	}
	
	function decodeStump($stump)
	{
		$data = Decrypt(AppSettings::stumpKey, $stump);
		return $data;
	}

	class Session
	{
		public $sid;
		public $stump;
		public $table;
		public $histTable;
		public $data;
		public $isValid = FALSE;
		
		public function __construct($stump){
			$dataString = decodeStump($stump);
			parse_str($dataString,$args);
			if( is_array($args) and count($args) >= 2 ){
				$table;
				$histTable;
				switch($args['type'])
				{
					case UserTypes::ADMIN:
						$table = new TableAdmSessions();
						$histTable = new TableAdmSessionsHistory();
						break;
					case UserTypes::USER:
						$table = new TableUsrSessions();
						$histTable = new TableUsrSessionsHistory();
						break;
					default:
					//NOTHNING TO DO
						break;
				}
				
				if( $table ){
					$this->stump = $stump;		
					$this->data = $args;
					$this->table = $table;
					$this->histTable = $histTable;
					$this->isValid = TRUE;
				}	
			}
		}
		
		public function getSid($dblink)
		{
			$result = FALSE;
			if( $this->isValid ){
				$rows = checkSession($dblink,$this->table,$this->stump);
				if( is_array($rows) and count($rows) == 1 ){
					$row = $rows[0];
					$this->sid = $row['id'];
					$this->data = array_merge($this->data,$row);
					$result = TRUE;
				}
			}
			return $result;
		}
		
		public function update($dblink)
		{
			$result = FALSE;
			if( $this->isValid and ( $this->sid > 0 ) ){
				$result = updateSession($dblink,$this->table,$this->sid);
			}
			return $result;
		}
		
		public function close($dblink)
		{
			$result = FALSE;
			if( $this->isValid and ( $this->sid > 0 ) ){
				$result = deleteSession($dblink,$this->table,$this->sid,$this->histTable);
			}
			return $result;		
		}
		
	}
?>