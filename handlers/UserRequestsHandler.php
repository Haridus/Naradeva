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
require_once('./general/stringf/string_proc.php');
require_once './general/region/region.php';
        
//-----------------------------------------------------------------------------------------------
    class RequestStatus
    {
	CONST CLOSED  = 0;
        CONST OPENED  = 1;
	CONST EXPIRED = 2;
	CONST DONE    = 3;	
    }
    
    class GameCompositionFlags
    {
        CONST NO_FLAGS = 0;
        CONST SEX_MAN   = 0x1;
        CONST SEX_WOMAN = 0x2;
        CONST SEX_BOTH  = 0x3;
        
    }
   
    function requestMoveToHist($dblink,$reqHistTable,$dataRow,$status,$comment,$grade = NULL)
    {
        $user_name = $dataRow['user_name'];
        $user_phone = $dataRow['user_phone'];
        $helpers = $dataRow['helpers'];
        $otime =$dataRow['otime'];
        $text = $dataRow['text'];
        $stump = $dataRow['stump'];
        
        
//        'reqid'
//        'refid'
//        'user_name'
//        'user_phone',
//	'categories',
//	'special_categories',
//	'helpers_count_requested',
//	'helpers_count',
//	'helpers',
//	"otime",
//      "ctime",
//        "sh",
//        "dl",
//        'region_section_0',
//        'region_section_1',
//        'region_section_2',
//        'region_section_3',
//        'region_section_4',
//        'region',
//	"reward",
//        "status",
//        "text",
//        "comment",
//        'stump'		
                
        $kv = array( 'reqid' => $dataRow['id'],
                     'refid' => $dataRow['refid'],
                     'user_name' => sql_field_string($user_name),
                     'user_phone' => sql_field_string($user_phone),
                     'categories' => $dataRow['categories'],
                     'special_categories' => $dataRow['special_categories'],
                     'helpers_count' => $dataRow['helpers_count'],
                     'helpers_signed' => $dataRow['helpers_signed'],
                     'helpers' => sql_field_string($helpers),
                     'otime' => sql_field_string($otime),
                     'ctime' => 'NOW()',
                     'sh'    => $dataRow['sh'],
                     'dl'    => $dataRow['dl'],
                     'region_section_0'  => $dataRow['region_section_0'],
                     'region_section_1'  => $dataRow['region_section_1'],
                     'region_section_2'  => $dataRow['region_section_2'],
                     'region_section_3'  => $dataRow['region_section_3'],
                     'region_section_4'  => $dataRow['region_section_4'],
                     'region'  => $dataRow['region'],
                     'reward'  => $dataRow['reward'],
                     'grade'   => $grade,
                     'status'  => $status,
                     'text'    => sql_field_string($text),
                     'comment' => sql_field_string($comment),
                     'stump'   => sql_field_string($stump)
                    );	
        
        $query = $reqHistTable->insert(array_keys($kv), array_values($kv));
        $stmt = prepare_and_execute($dblink, $query, array());
        if(!$stmt){
           //TODO: handle err
        }
        $stmt = NULL;
    }
    
    //TODO think maybe remove transaction here and allow do it explisitly to make possible stack them in single transaction externally
    function closeRequest($dblink, $id = NULL, $dataRow = NULL, $reason = NULL, $comment = NULL, $reqHistTable = NULL, $grade = NULL)
    {
        $result = FALSE;
        if( $id ){
            //TODO:
        }
        if( $dataRow ){
            $id = $dataRow['id'];
            $helpers = $dataRow['helpers'];
            $helpers_signed = $dataRow['helpers_signed'];
            
            $whereClause = NULL;
            if( $helpers_signed > 0 && strlen($helpers) > 0 ){
                $whereClauseEntries = array();
                $helpersArr = explode(",", $helpers);
                foreach ($helpersArr as $value) {
                    $value = trim($value);
                    $whereClauseEntries[] = " ( refid = $value ) "; 
                }
                $whereClause = sprintf("WHERE %s LIMIT $helpers_signed", implode("OR", $whereClauseEntries) );   
            }
                                    
            $dblink->beginTransaction();          
            
            if( $whereClause ){
                $ptable = new TableUsersProfiles();
                $query =  $ptable->update($whereClause,array('current_request'),'NULL');
                $stmt = prepare_and_execute($dblink,$query,array());
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to clean request(1)","",__FILE__.__LINE__);
                }		
                $stmt = NULL;  
            }         
            
            if( $reqHistTable ){
                requestMoveToHist($dblink,$reqHistTable,$dataRow,$reason,$comment,$grade);
            }
            
            $reqTable = new TableUsersRequest();
            $query = $reqTable->delete("WHERE (id = $id) LIMIT 1 ");
            $stmt = prepare_and_execute($dblink, $query,array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to clean request(2)","",__FILE__.__LINE__);
            }		
            $stmt = NULL;            
            
            $dblink->commit();
            
            $result = TRUE;
        }
        return $result;
    }
    
    function closeRequestBy($dblink,$phone = NULL,$reason=NULL)
    {
        $phone = PhoneConverter::convert($phone);
        
        $reqTable = new TableUsersRequest();
        $reqHistTable = new TableUsersRequestHistory();
     
        $whereClauseEntryes = [];
        if( $phone ){
            $whereClauseEntryes[] = "(user_phone='$phone')";
        }
        $whereClause = "WHERE ".implode(" AND ",$whereClauseEntryes);
        
        $query = $reqTable->select($whereClause);
//        echo var_dump($reqData)."<br>";
//        echo $query."<br>";
        $stmt = prepare_and_execute($dblink, $query);
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to check user data(1)");
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
//        echo var_dump($row)."<br>";
        if( $row ){
            closeRequest($dblink,NULL,$row,RequestStatus::CLOSED,$reason,$reqHistTable);
        }
        else{
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"no such request(1)");
        }
        $stmt = NULL;
    }
        
    function cleanRequests($dblink,$refid = NULL)
    {
        $reqTable = new TableUsersRequest();
        //$reqHistTable = new TableUsersRequestHistory();
        
        $whereClauseEntryes = array();
        if( $refid and $refid > 0 ){
            $whereClauseEntryes[] = "( refid = $refid )";
        }
        $whereClauseEntryes[] = "(  NOW() > ctime  )";
        // $whereClauseEntryes[] = "(  players_signed = 0 )";
        
        $limit = NULL;
        if( $refid ){
            $limit = "LIMIT 1";
        }
        $whereClause = sprintf("WHERE %s %s", implode(" and ", $whereClauseEntryes), $limit );
        
        $query = $reqTable->select($whereClause);
        $stmt  = prepare_and_execute( $dblink, $query, array() );
        if( $stmt ){
            $rows = $stmt->fetchAll();
            $stmt = NULL;
            if( count($rows) > 0 ){                
                foreach ($rows as $row) {
                    closeRequest($dblink, NULL, $row, RequestStatus::EXPIRED,"");
                }
            }
//            $query = $reqTable->delete($whereClause);
//            $stmt = prepare_and_execute( $dblink, $query, array() );
//            if(!$stmt){
//                //TODO: error handle
//            }
            $stmt = NULL;
        }
        
        //TODO: update helpers tables if needed ?
    }
            
    function updateRequests($dblink,$refid)
    {
        $result = FALSE;
       //oblosete: not here
//        $reqTable = new TableUsersRequest();
//        $reqTableName = $reqTable->name;
//            
//        $query = "UPDATE $reqTableName SET ctime=ADDTIME( NOW(),'0:30:0' ) WHERE ( ctime <= NOW() ) and (refid = $refid) ";
//        $stmt = prepare_and_execute( $dblink, $query, array() );
//        if( $stmt ){
//            $result = TRUE;
//        }
        return $result;
    }	
	

//----------------------------------------------------------------------------------------        
class AddRequestHandler extends OperationsHandler
{
    public function __construct($operation, $session) {
        parent::__construct($operation, $session, Operations::ADD_REQUEST);
    }
    
    public function handleBody($dblink, $session) : Response
    {
        //may be such operation may be speed up if user is registred because we can extract information by id
        $result = NULL;
        $this->operation->getArg(0,$key);
        $this->operation->getArg(1,$user_name);
        $this->operation->getArg(2,$user_phone);
        $this->operation->getArg(3,$sh);
        $this->operation->getArg(4,$dl);
        $this->operation->getArg(5,$cats);
        $this->operation->getArg(6,$specats);
        $this->operation->getArg(7,$helpers_requested);
        $this->operation->getArg(8,$reward);
        $this->operation->getArg(9,$text);
        
        if( $key != AppSettings::requestKey ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad key");
        }
        if( strlen($user_name) == 0 ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad user name");
        }
        
        $user_phone = PhoneConverter::convert($user_phone);
        $specats = urldecode($specats);
        $text = urldecode($text);
        
        $tableUserRequests = new TableUsersRequest();
        $query = $tableUserRequests->select("WHERE user_phone = '$user_phone'");
        $stmt = prepare_and_execute($dblink, $query);
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to check user data(1)");
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if( $row ){
            //there is a more resent request
            closeRequest($dblink,NULL,$row, RequestStatus::CLOSED,"closed by new request",new TableUsersRequestHistory());
        }
        $stmt = NULL;
        
//        'id' autovalue
//        'refid'
//        'user_name'
//        'user_phone'
//	'categories'
//	'special_categories',
//	'helpers_count',
//	'helpers_signed',
//	'helpers',
//	 "otime",
//         "ctime",
//         "sh",
//         "dl",
//        'region_section_0',
//        'region_section_1',
//        'region_section_2',
//        'region_section_3',
//        'region_section_4',
//        'region',
//	"reward",
//        "status",
//        "text",
//        'stump'
        
        $regionHandlers = calculateRegionHandlers_0($sh, $dl);
        $region = regionFromHandlers_0($regionHandlers);
        
        $kv = array('user_name'  => $user_name,
                    'user_phone' => $user_phone,
                    'sh' => $sh,
                    'dl' => $dl
                   );
        $stump = make_obj_stump($kv);
        
        $kv = array('refid' => 0,//may be refid if user is logged in 
                    'user_name' => sql_field_string($user_name),
                    'user_phone' => sql_field_string($user_phone),
                    'categories' => $cats,
                    'special_categories' => sql_field_string($specats),
                    'helpers_count' => $helpers_requested,
                    'helpers_signed' => 0,
                    'helpers' => 'NULL',
                    'otime' => 'NOW()',
                    'ctime' => "ADDTIME( NOW(),'0:30:0' )",
                    'sh'    => $sh,
                    'dl'    => $dl,
                    'region_section_0' => $regionHandlers[0],
                    'region_section_1' => $regionHandlers[1],
                    'region_section_2' => $regionHandlers[2],
                    'region_section_3' => $regionHandlers[3],
                    'region_section_4' => $regionHandlers[4],
                    'region'=>$region,
                    'reward'=>$reward,
                    'status' => RequestStatus::OPENED,
                    'text' => sql_field_string($text),
                    'stump' => sql_field_string($stump)
            );

        $query = $tableUserRequests->insert(array_keys($kv), array_values($kv));
        $stmt = prepare_and_execute($dblink, $query);
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to place request(1)");
        }
        $stmt = NULL;
        
        $result = new Response($this->operation->operation, ErrorCodes::OK, $stump);
        
        return $result;        
    }
}

class CloseRequestHandler extends OperationsHandler
{
    public function __construct($operation, $session)
    {
        parent::__construct($operation,$session,Operations::CLOSE_REQUEST);
    }
    
    public function handleBody($dblink, $session) : Response
    {
        //may be such operation may be speed up if user is registred because we can extract information by id
        $result = NULL;
        $this->operation->getArg(0,$stump);
        $this->operation->getArg(1,$reason);
        $this->operation->getArg(2,$comment);
        $this->operation->getArg(3,$grade);
        
        $reqData = decode_obj_stump($stump);
        if( !array_key_exists('user_phone', $reqData)){
            throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad data(1)");
        }
        
        $user_phone = $reqData['user_phone'];
        $user_phone = trim($user_phone); //user_phone + etc simbols are corrupted by Encrypt/Decrypt bacause they constructed to make url valid stumps etc.
        if( $user_phone[0] != '+'){$user_phone = "+".$user_phone;} //thats why we must restole corrent phone phormat
        $user_phone = PhoneConverter::convert($user_phone);
        
        $reqTable = new TableUsersRequest();
        $reqHistTable = new TableUsersRequestHistory();
        
        $query = $reqTable->select("WHERE user_phone='$user_phone'");
//        echo var_dump($reqData)."<br>";
//        echo $query."<br>";
        $stmt = prepare_and_execute($dblink, $query);
        if( !$stmt ){
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to check user data(1)");
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
//        echo var_dump($row)."<br>";
        if( $row ){
            switch ($reason){
                case RequestStatus::DONE:
                    closeRequest($dblink,NULL,$row,$reason,$comment,new TableUsersRequestHistory(),$grade);
                    break;
                case RequestStatus::CLOSED:
                default:
                    closeRequest($dblink,NULL,$row,RequestStatus::CLOSED,"closed by user[$reason]:".$comment,new TableUsersRequestHistory());    
                    break;
            }
        }
        else{
            throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"no such request(1)");
        }
        $stmt = NULL;                        
        
        $result = new Response($this->operation->operation, ErrorCodes::OK,1);
        
        return $result;
    }	
}



//class ChangeRequestHandler extends AutorazedOperationHandler
//    {
//        public function __construct($operation, $session)
//        {
//            parent::__construct($operation,$session,Operations::CHANGE_REQUEST);
//        }
//		
//        public function handleBodyEx($dblink, $session, $user) : Response
//        {
//            $result = NULL;
//                
//            if( !( $user->data['rights'] & UserRights::SELF_REQUEST_CHANGE ) ){
//                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you can't change requests: no rights","",__FILE__.__LINE__);  			
//            }
//		
//            $refid   = $user->id;
//            cleanRequests($dblink, $refid);                            
//            
//            $this->operation->getArg(0,$stump);
//            $this->operation->getArg(1,$sh);
//            $this->operation->getArg(2,$dl);
//            $this->operation->getArg(3,$timestamp);
//            $this->operation->getArg(4,$pcount);
//            $this->operation->getArg(5,$age);
//            $this->operation->getArg(6,$level);
//            $this->operation->getArg(7,$composition);
//            $this->operation->getArg(8,$self_role);
//            $this->operation->getArg(9,$text);
//                        
//            $kv = array();
//            if( $sh and $dl ){
//                $kv['sh'] = $sh;
//                $kv['dl'] = $dl;
//                
//                $regionHandlers = calculateRegionHandlers_0($sh, $dl);
//                $region = regionFromHandlers_0($regionHandlers);
//                
//                $kv['region_section_0'] = $regionHandlers[0];
//                $kv['region_section_1'] = $regionHandlers[1];
//                $kv['region_section_2'] = $regionHandlers[2];
//                $kv['region_section_3'] = $regionHandlers[3];
//                $kv['region_section_4'] = $regionHandlers[4];
//                $kv['region']           = $region;
//            }
//            if( $timestamp ){
//                $stumpData  = decode_obj_stump($stump);
//                $ctimestump = $stumpData['ctimestamp'];
//                $oldtimestump = $stumpData['timestamp'];
//                $timediff   = $timestamp - $oldtimestump;
//                $kv['ctime']= "from_unixtime( unix_timestamp( ctime )+($timediff))";
//                $kv['time'] = "from_unixtime($timestamp)";
//            }
//            if( $pcount ){
//                $kv['pcount_requested'] = $pcount;
//            }
//            if( $age ){
//                $kv['age'] = $age;
//            }
//            if( $level ){
//                $kv['level'] = $level;
//            }
//            if( $composition ){
//                $kv['composition'] = $composition;
//            }
//            if( $self_role ){
//                //TODO: nothing yet
//            }
//            if( strlen($text) > 0 ){
//                $text = urldecode($text);
//                $kv['text'] = sql_field_string($text);
//            }
//            
//            if( count($kv) > 0 ){
//                $reqTable = new TableUsersRequest();
//                $query = $reqTable->update("WHERE (refid = $refid) LIMIT 1",array_keys($kv), array_values($kv));
//                $stmt = prepare_and_execute($dblink, $query, array());
//                if( !$stmt ){
//                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update request data, maybe olready closed","",__FILE__.__LINE__);
//                }
//            }
//                          
//            $result  = new Response($this->operation->operation,ErrorCodes::OK,0);
//            return $result;
//        }	
//    }
//        
    class GetRequestInfoHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_REQUEST_INFO);
        }
		
        public function handleBody($dblink, $session) : Response
        {
            $result = NULL;
                
            $this->operation->getArg(0,$key);
            $this->operation->getArg(1,$stump);
 
            if( $key != AppSettings::requestKey ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad key");
            }
            
            $reqData = decode_obj_stump($stump);
            if( !array_key_exists('user_phone', $reqData)){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad data(1)");
            }
            
            $user_phone = $reqData['user_phone'];
            $user_phone = trim($user_phone); //user_phone + etc simbols are corrupted by Encrypt/Decrypt bacause they constructed to make url valid stumps etc.
            if( $user_phone[0] != '+'){$user_phone = "+".$user_phone;} //thats why we must restole corrent phone phormat
            $user_phone = PhoneConverter::convert($user_phone);
            
            $whereClauseEntryes = [];
            if( $user_phone ){
                $whereClauseEntryes[] = "(user_phone='$user_phone')";
            }
            $whereClause = "WHERE ".implode(" AND ",$whereClauseEntryes);
     
            $reqTable = new TableUsersRequest();
            $reqHistTable = new TableUsersRequestHistory();
      
            //'user_name''user_phone''categories''special_categories''helpers_count''helpers_signed'
            //'helpers' "otime""ctime""sh""dl"'region'"reward""status""text"'stump'
            
            $query = $reqTable->select($whereClause, array('id',/*'sh','dl','region',*/'otime','ctime','status','helpers_count','helpers_signed','helpers') );
//          echo var_dump($reqData)."<br>";
//          echo $query."<br>";
            $stmt = prepare_and_execute($dblink, $query);
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to fetch user data(1)");
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if( !$row ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_FAIL,"fail to fetch user data(2)");
            }
            
            unset($row['id']);
            $otime = new DateTime( $row["otime"] );
            $ctime = new DateTime( $row["ctime"] );
            $ntime = new DateTime();
            
            if( $ctime < $ntime ){
                //Close if nobody connected
                if( $row["helpers_signed"] > 0 ){
                    //DO NOTHING
                    //request stay opened till be closed by user ??? or interval more than a day
                }
                else{
                    closeRequest($dblink, NULL, $row, RequestStatus::EXPIRED, "expired", $reqHistTable);
                    throw new ProcessingExeption(ErrorCodes::OPERATION_REQUEST_EXPIRED,"request expired");
                }
            } 
            
            //helpers_signed helpers_count helpers                             
            if( $row['helpers_signed'] > 0 and strlen( $row['helpers'] ) > 0 ){
                $helpers_signed = $row['helpers_signed'];
                $helpers = $row['helpers'];
                $parr = explode(",", $helpers);
                
                $wherePclause = [];
                foreach ($parr as $value) {
                    $wherePclause[] = " (m.id = $value) ";
                }
                $whereClause = sprintf(" WHERE %s LIMIT $helpers_signed ", implode("or", $wherePclause));
             
                $mainTable = new TableUsers();
                $profileTable = new TableUsersProfiles();
                $locationTable = new TableUsersLocation();
                    
                $usersTableName    = $mainTable->name;
                $profileTableName  = $profileTable->name;
                $locationTableName = $locationTable->name;                  
                                
                $query = "SELECT m.mail as mail,      "
                        . "      m.phone as phone,    "
                        . "      p.name as name,      "
                        . "      p.sName as sName,    "
                        . "      p.fName as fName,    "
                        . "      p.birth as birth,    "
                        . "      l.sh as sh,          "
                        . "      l.dl as dl,          "
                        . "      l.region as region   "
                        . "FROM $usersTableName as m  "
                        . "INNER JOIN $profileTableName as p ON m.id = p.refid "
                        . "INNER JOIN $locationTableName as l ON m.id = l.refid "
                        . $whereClause;
                    
                $stmt = prepare_and_execute($dblink, $query, array());
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get helpers data","",__FILE__.__LINE__);
                }
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                unset( $row['helpers'] );
                $row['helpers'] = $rows;
                $stmt = NULL;
            }
            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$row);
            return $result;
        }
    }
//        
//    class GetCurrentRequestHandler extends AutorazedOperationHandler
//    {
//        public function __construct($operation, $session)
//        {
//            parent::__construct($operation,$session,Operations::GET_CURRENT_REQUEST);
//        }
//		
//        public function handleBodyEx($dblink, $session, $user) : Response
//        {
//            $result = NULL;
//                
//            if( !($user->data['rights'] & UserRights::SELF_REQUEST_VIEW ) ){
//                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you can't change requests: no rights","",__FILE__.__LINE__);  			
//            }
//		
//            $id = $user->id;
//            cleanRequests($dblink, $id);
//                                
//            $reqTable = new TableUsersRequest();
//            $query = $reqTable->select("WHERE (refid = $id) LIMIT 1");
//            $stmt = prepare_and_execute($dblink, $query, array());
//            if( !$stmt ){
//                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get current request","",__FILE__.__LINE__);
//            }                
//            $row = $stmt->fetch(PDO::FETCH_ASSOC);
//            $stmt = NULL;
//            
//            $stump = NULL;
//            if( $row and ( count($row) > 0 ) ){
//                $stump = $row['stump'];
//            }            
//            
//            $result  = new Response($this->operation->operation,ErrorCodes::OK,$stump);
//            return $result;
//        }	
//    }
//
