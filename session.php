<?php
require_once('LocalSettings.php');
require_once('AppTables.php');
require_once('PDO/connect.php');
require_once './general/stump/stump.php';
require_once './general/query/query.php';

    class SessionStatus
    {
        CONST CLOSED = 0;
	CONST OPENED = 1;
	CONST EXPIRED = 2;	
    }

    function openSession($dblink, $sessionTable, $refid)
    {
        $userType = $sessionTable::type;      
        $kv = array('type'  => $userType,
                    'refid' => $refid,
                    'otime' => date('Y-m-d H:i:s')
                   );
        $stump = make_obj_stump($kv); 
                
        $kv = array('refid' => $refid,
                    'ctime' => "ADDTIME(NOW(),'0:15:0')",
                    'stump' => sql_field_string($stump)
        );
                
        $query = $sessionTable->insert(array_keys($kv), array_values($kv));					                  
        $stmt = prepare_and_execute( $dblink, $query, array() );
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to open session","",__FILE__.__LINE__);
        }
        $sid = $dblink->lastInsertId();
        $result = $stump;
        $stmt = NULL;
        
        return $result;        
    }
    
    function checkSession($dblink,$sessionTable,$stump = NULL,$refid = NULL)
    {
        $result = NULL;
        
        $whereClauseEntryes = [];
        if( $refid ){
            $whereClauseEntryes[] = " ( refid = $refid ) ";
        }
        elseif( $stump and strlen($stump) > 0 ){
            $whereClauseEntryes[] = " ( stump LIKE '$stump' ) ";
        }
        
        if( count( $whereClauseEntryes ) > 0 ){
            $whereClause = sprintf("WHERE %s LIMIT 1", implode("and", $whereClauseEntryes) );
            $query = $sessionTable->select($whereClause);
            $stmt = prepare_and_execute($dblink,$query,array());
            if( $stmt ){
                $result = $stmt->fetch(PDO::FETCH_BOTH);
            }
            $stmt = NULL;
        }
        return $result;
    }
	
    function cleanSessions($dblink, $sessionTable, $histTable = NULL, $refid = NULL)
    {
        $result = 0;
        
        $whereClauseEntryes = [];
        if( $refid and $refid > 0 ){
            $whereClauseEntryes[] = "(refid = $refid)";
        }
        $whereClauseEntryes[] = "( ctime <= NOW() )";
        
        $whereClause = sprintf("WHERE %s ", implode("and", $whereClauseEntryes) );
        
        if( $histTable ){
            $query = $sessionTable->select($whereClause);
            $stmt = prepare_and_execute($dblink,$query,array());
            if( $stmt ){
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                for($i =0; $i<count($rows); $i++){
                    $row = $rows[$i];
                    $id  = $row['id'];
                    $refid = $row['refid'];
                    $otime = $row['otime'];
                    $ctime = $row['ctime'];
                    $stump = $row['stump'];
                    $status = SessionStatus::EXPIRED;

                    $query = $histTable->insert( array('id','refid','otime','ctime','stump','status'),
                                                 array("$id","$refid","'$otime'","NOW()","'$stump'","$status")
                                               );
                    $stmt = prepare_and_execute($dblink,$query,array());
                }
            }
            $stmt = NULL;
        }

        $query = $sessionTable->delete($whereClause);
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
        $query = "UPDATE $table SET ctime=ADDTIME(NOW(),'0:15:0') WHERE (id = $sid) LIMIT 1";
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
        
        $whereClause = "WHERE (id = $sid) LIMIT 1";
        
        if( $histTable ){
            $query = $sessionTable->select($whereClause);
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

        $query = $sessionTable->delete($whereClause);
        $stmt = prepare_and_execute($dblink,$query,array());
        if( $stmt ){
            $result = TRUE;										
        }
        $stmt = NULL;
        return $result;
    }

//--------------------------------------------------------------------------------------
    class Session
    {
    	public $sid;
	public $stump;
	public $table;
	public $histTable;
	public $data;
	public $isValid = FALSE;
		
	public function __construct($stump){
            $args = decode_obj_stump($stump);
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
		$row = checkSession($dblink,$this->table,$this->stump,$this->data['refid']);
                if( is_array($row) and count($row) > 1 ){
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