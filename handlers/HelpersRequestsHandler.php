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
require_once('./general/datetime/datetime.php');

//-----------------------------------------------------------------------------------
    class GetHelpersCandidatesCount extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_HELPERS_CANDIDATES_COUNT);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            if(!( ($user->type == UserTypes::ADMIN)           and
                  ($user->data['rights'] & UserRights::URS_INFO_VIEW > 0) 
                ) ){
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you have no rights for this operation","",__FILE__.__LINE__); 
            }
            
            $profilesTable = new TableUsersProfiles();
            $tableName = $profilesTable->name;
            $query = "SELECT COUNT(refid) as count FROM $tableName WHERE (want_help > 0) and (checked = 0) ";
            $stmt = prepare_and_execute( $dblink, $query, array() );
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get candicates count","",__FILE__.__LINE__);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $row['count'];
            $stmt = NULL;
            
            return new Response($this->operation->operation,ErrorCodes::OK,$count);
        }
    }
    
    class GetHelpersCandidatesList extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_HELPERS_CANDIDATES_LIST);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            if(!( ($user->type == UserTypes::ADMIN)           and
                  ($user->data['rights'] & UserRights::URS_INFO_VIEW > 0) 
                ) ){
                throw new ProcessingExeption(ErrorCodes::NO_RIGHTS,"you have no rights for this operation","",__FILE__.__LINE__); 
            }
            
            $this->operation->getArg(0,$offset);
            $this->operation->getArg(1,$count);
            
            if( $offset <0  or $count <=0 or $count>256/*magic number*/ ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad offset/count values","",__FILE__.__LINE__); 
            }
            
            $usersTable           = new TableUsers();
            $profilesTable        = new TableUsersProfiles();
            $utableName = $usersTable->name;
            $pTableName = $profilesTable->name;
            
            //"name""sName""fName""birth""birth_time""birth_place""sex""docType""docNum""docInfo""docImage""want_help""checked""admitted""current_request"
            
            $query = "SELECT u.login  as login,     "
                    . "      u.mail   as mail ,     "
                    . "      u.phone  as phone,     "
                    . "      u.role   as role ,     "
                    . "      u.rights as rights,    "
                    . "      p.name   as name, "
                    . "      p.sName  as sName,"
                    . "      p.fName  as fName,"
                    . "      p.birth  as birth,"
                    . "      p.sex    as sex,     "
                    . "      p.docType as docType,"
                    . "      p.docNum  as docNum ,"
                    . "      p.docInfo as docInfo,"
                    . "      p.want_help as want_help, "
                    . "      p.checked   as checked  , "
                    . "      p.admitted  as admitted , "
                    . "      u.stump  as stump,        "
                    . "      u.last_visit  as last_visit "
                    . "FROM $pTableName as p                         "
                    . "INNER JOIN $utableName as u ON p.refid = u.id "
                    . "WHERE (want_help > 0 ) and (checked = 0)                      "
                    . "LIMIT $offset, $count                         ";
                        
            $stmt = prepare_and_execute( $dblink, $query, array() );
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get candicates list","",__FILE__.__LINE__);
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = NULL;
            
            return new Response($this->operation->operation,ErrorCodes::OK,$rows);
        }
    }   
    
    function set_helpers_data_checked($dblink, $user, $ok)
    {
        $result = true;
        $id = $user->id;        
        $profileTable = new TableUsersProfiles();
        $query = $profileTable->update("WHERE refid = $id LIMIT 1",array('checked'),array($ok) );
        $stmt = prepare_and_execute( $dblink, $query, array() );
        if( !$stmt ){
            $result = false;
        }        
        return $result;
    }
    
    class SetHelpersDataCheckedHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::SET_HELPERS_DATA_CHECKED);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;

            if(  !( ($user->type == UserTypes::ADMIN) and ( $user->data['rights'] & UserRights::USR_CHANGE == UserRights::USR_CHANGE ) ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
            
            $this->operation->getArg(0,$stump);
            $this->operation->getArg(1,$ok);
  
            $ok = $ok > 0 ? 1 : 0;          
            $usrData = decode_obj_stump($stump);
            
            $refUser = new User();
            if( !$refUser->getCoreData( $dblink, $usrData['login'] ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user info","",__FILE__.__LINE__);
            } 
            
            if( !set_helpers_data_checked($dblink, $refUser,$ok) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail set helper data checked flag","",__FILE__.__LINE__);
            }
            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$ok);
            return $result;
        }
    }
    
    class AdmittHelperHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::ADMITT_HELPER);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;

            if(  !( ($user->type == UserTypes::ADMIN) and ( $user->data['rights'] & UserRights::USR_CHANGE == UserRights::USR_CHANGE ) ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
            
            $this->operation->getArg(0,$stump);
            $this->operation->getArg(1,$ok);
  
            $ok = $ok > 0 ? 1 : 0;          
            $usrData = decode_obj_stump($stump);
            
            $refUser = new User();
            if( !$refUser->getCoreData( $dblink, $usrData['login'] ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user info","",__FILE__.__LINE__);
            } 
            
            $refUser->get($dblink,1);
            if( !($refUser->data["checked"] > 0) ){
                throw new ProcessingExeption(ErrorCodes::USER_CONDITION_VIOLATION,"used data are unchecked yet","",__FILE__.__LINE__);
            }
            
            //"name""sName""fName""birth""birth_time""birth_place""sex""docType""docNum""docInfo""docImage""want_help""checked""admitted""current_request"    
            if( $ok > 0 ){
                //promote to helper
                $id = $refUser->id;
                $usersTable = new TableUsers();
                $profileTable = new TableUsersProfiles();
                
                $uTableName = $usersTable->name;
                $pTableName = $profileTable->name;
                $role       = UserRoles::HELPER;
                $rights     = UserRights::HELPER_STANDART;
                
                $query = "Update $uTableName as u "
                        ."INNER JOIN $pTableName as p ON u.id = p.refid "
                        ."SET u.role = $role, u.rights = $rights, p.admitted = $ok "
                        ."WHERE u.id = $id";
                
                $stmt = prepare_and_execute( $dblink, $query, array() );
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to admitt helper","",__FILE__.__LINE__);
                }
            }
            else{
                //demote to user
                $id = $refUser->id;
                $usersTable = new TableUsers();
                $profileTable = new TableUsersProfiles();
                
                $uTableName = $usersTable->name;
                $pTableName = $profileTable->name;
                $role       = UserRoles::USER;
                $rights     = UserRights::USR_STANDART;
                
                $query = "Update $uTableName as u "
                        ."INNER JOIN $pTableName as p ON u.id = p.refid "
                        ."SET u.role = $role, u.rights = $rights, p.admitted = 0 "
                        ."WHERE u.id = $id";
                
                $stmt = prepare_and_execute( $dblink, $query, array() );
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to unadmitt helper","",__FILE__.__LINE__);
                }                
            }
                
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$ok);
            return $result;
        }
    }
    
    class SetHelperDataCheckedAndAdmittHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::SET_HELPER_DATA_CHECKED_AND_ADMITT);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;

            if(  !( ($user->type == UserTypes::ADMIN) and ( $user->data['rights'] & UserRights::USR_CHANGE == UserRights::USR_CHANGE ) ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
            
            $this->operation->getArg(0,$stump);
            $this->operation->getArg(1,$checked);
            $this->operation->getArg(2,$admitted);
              
            $checked = $checked   > 0 ? 1 : 0;
            $admitted = $admitted > 0 ? 1 : 0;
            
            if( $checked == 0 && $admitted > 0 ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad parameters - admitt without check ","",__FILE__.__LINE__);
            }
            
            $usrData = decode_obj_stump($stump);
            
            $refUser = new User();
            if( !$refUser->getCoreData( $dblink, $usrData['login'] ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user info","",__FILE__.__LINE__);
            } 
            
            if( $checked == 0 & $admitted == 0 ){
                //strange by this can be [while testing]
                $id = $refUser->id;
                $usersTable = new TableUsers();
                $profileTable = new TableUsersProfiles();
                
                $uTableName = $usersTable->name;
                $pTableName = $profileTable->name;
                $role       = UserRoles::USER;
                $rights     = UserRights::USR_STANDART;
                
                $query = "Update $uTableName as u "
                        ."INNER JOIN $pTableName as p ON u.id = p.refid "
                        ."SET u.role = $role, u.rights = $rights, p.checked = 0, p.admitted = 0 "
                        ."WHERE u.id = $id";
                
                $stmt = prepare_and_execute( $dblink, $query, array() );
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to uncheck and unadmitt user","",__FILE__.__LINE__);
                }
            }
            else if( $checked > 0 & $admitted == 0 ){
                //functions like as set_data_checked
                if( !set_helpers_data_checked($dblink,$refUser,$checked) ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail set helper data checked flag","",__FILE__.__LINE__);
                }                
            }
            else if( $checked > 0 & $admitted > 0 ){
                $id = $refUser->id;
                $usersTable = new TableUsers();
                $profileTable = new TableUsersProfiles();
                
                $uTableName = $usersTable->name;
                $pTableName = $profileTable->name;
                $role       = UserRoles::HELPER;
                $rights     = UserRights::HELPER_STANDART;
                
                $query = "Update $uTableName as u "
                        ."INNER JOIN $pTableName as p ON u.id = p.refid "
                        ."SET u.role = $role, u.rights = $rights, p.checked = $checked, p.admitted = $admitted "
                        ."WHERE u.id = $id";
                
                $stmt = prepare_and_execute( $dblink, $query, array() );
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to check and admitt user","",__FILE__.__LINE__);
                }
            }
                            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$checked | $admitted);
            return $result;
        }
    }
    
    class GetRequestsList extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GET_REQUESTS_LIST);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;

            //Maybe allow Admins view users requests on map?
            if( 
                   !( $user->data['role'] == UserRoles::HELPER and 
                    //  $user->data['admitted'] > 0              and //already included in role&rights
                    ( $user->data['rights'] & UserRights::URS_REQUEST_VIEW > 0 ) 
                 ) 
              ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
            $user->get($dblink,1);
                                
            $this->operation->getArg(0,$sh); 
            $this->operation->getArg(1,$dl);
            $this->operation->getArg(2,$cats);
            $this->operation->getArg(3,$specats);
            
            $regions = calculateNearbyRegions_0($sh, $dl);
            $regions[] = calculateRegion_0($sh, $dl);
            
            $whereRClauseEntryes = [];
            foreach ($regions as $value) {
                $whereRClauseEntryes[] = "(region = $value)";                
            }
            $whereRclause = implode(" OR ", $whereRClauseEntryes);
                                  
            $wheleClauseEntryes = array();
            $wheleClauseEntryes[] = "( $whereRclause )";
            
            if( $user->data["sex"] == UserSexs::MAN ){
                $wheleClauseEntryes[] = "( ( (categories & 0x2) = 0 ) OR ( (categories & 0x3) = 0x3 ) )"; //NOT WOMAN OR BOTH
            }
            else if($user->data["sex"] == UserSexs::WOMAN){
                $wheleClauseEntryes[] = "( ( (categories & 0x1) = 0) OR ( (categories & 0x3) = 0x3 ) )";//NOT MAN OR BOTH
            }
            else{//UserSexs::NO UserSexs::BOTH
                $wheleClauseEntryes[] = "( ( (categories & 0x3) = 0 ) OR ( (categories & 0x3) = 0x3 ) )"; //NO OR BOTH
            }
            $cats = ($cats & ~0x3); //clear MAN WOMAN flags
            if( $cats and $cats > 0 ){    
                $wheleClauseEntryes[] = "( (categories & $cats) > 0 )";
            }
            if( $specats and strlen( $specats ) > 0 ){ 
                $wheleClauseEntryes[] = "( MATCH(special_categories) AGAINST('$specats') )";
            }
            $whereClause = "WHERE ".implode(" and ",$wheleClauseEntryes);
            
//            'id''refid''user_name''user_phone''categories''special_categories''helpers_count'
//            'helpers_signed''helpers'"otime""ctime""sh""dl"
//            'region_section_0''region_section_1''region_section_2''region_section_3''region_section_4''region'
//            "reward""status""text"'stump'				            
            
            $requestTable = new TableUsersRequest();
            $query = $requestTable->select($whereClause, array('user_name'/*,'user_phone'*/,'otime','ctime','sh','dl','region','categories','special_categories','helpers_count','helpers_signed','status','reward','text','stump'));
//            echo $query."<br>";
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get requests info","",__FILE__.__LINE__);
            }
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = NULL;
            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$rows);
            return $result;
        }
    }
    
//TODO needs revising    
    class TakeUpCallHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::TAKE_UP_CALL);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            
            if(
                 !( $user->data['role'] == UserRoles::HELPER and 
                    //  $user->data['admitted'] > 0              and //already included in role&rights
                    ( $user->data['rights'] & UserRights::URS_REQUEST_SIGN_UP > 0 ) 
                 ) 
              ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
            
            $this->operation->getArg(0,$reqstump);
            $reqData = decode_obj_stump($reqstump);

            //$kv = array('user_name'  => $user_name,
//                    'user_phone' => $user_phone,
//                    'sh' => $sh,
//                    'dl' => $dl
//                   );
//$stump = make_obj_stump($kv);            
            
            if( !( $reqData and array_key_exists('user_phone', $reqData) ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad stump data","",__FILE__.__LINE__);
            }
            
            $user_phone = $reqData['user_phone'];
            $user_phone = trim($user_phone); //user_phone + etc simbols are corrupted by Encrypt/Decrypt bacause they constructed to make url valid stumps etc.
            if( $user_phone[0] != '+'){$user_phone = "+".$user_phone;} //thats why we must restole corrent phone phormat
            $user_phone = PhoneConverter::convert($user_phone);
            $phone = sql_field_string($user_phone);
            
            $requestTable = new TableUsersRequest();
            $query = $requestTable->select("WHERE (user_phone = $phone) LIMIT 1", array('user_name','user_phone','categories','special_categories','helpers_count','helpers_signed','helpers','sh','dl','status','text','stump'));
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get request data(1)","",__FILE__.__LINE__);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if( !$row ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get request data(2)","",__FILE__.__LINE__);
            }
            $stmt = NULL;
              
//TODO maybe cats checks sex checks etc.             
            if( !$user->get($dblink,1) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get user data","",__FILE__.__LINE__);
            }
            
            if( $user->data["current_request"] === $reqstump ){
                //NOTHING TO DO return row
            }
            else{
                $helpers_count = $row["helpers_count"];
                $helpers_signed = $row["helpers_signed"];
    //          if( $helpers_count == $helpers_signed ){ // NOT NOW MAYBE LATER 
    //                throw new ProcessingExeption(ErrorCodes::OPERATION_CONDITION_INCONSISTENCY,"helpers count already reached","",__FILE__.__LINE__);
    //          }

                $userid = "".$user->id;
                $helpers = trim( $row["helpers"] );
                $helpers_arr = [];
                if( strlen( $helpers ) != 0 ){
                    $helpers_arr = explode(",", $helpers);
                }

                if( !in_array("$userid", $helpers_arr) ){
                    $helpers_arr[] = $userid;
                    $helpers = implode(",", $helpers_arr);
                    $helpers_signed = count($helpers_arr);

                    $dblink->beginTransaction();

                    $query = $requestTable->update("WHERE (user_phone = $phone) LIMIT 1", array('helpers','helpers_signed'), 
                                                                                     array(sql_field_string($helpers),$helpers_signed)
                                                  );
                    $stmt = prepare_and_execute($dblink, $query, array());
                    if( !$stmt ){
                        throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to sign up(1)","",__FILE__.__LINE__);
                    }

                    $stump = $row["stump"];
                    $ptable = $user->dataSources[1];
                    $query  = $ptable->update("WHERE ( refid = $userid ) LIMIT 1 ",array('current_request'),array( sql_field_string($stump) ) ); 
                    $stmt = prepare_and_execute($dblink, $query, array());
                    if( !$stmt ){
                        throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to sign up(2)","",__FILE__.__LINE__);
                    }

                    $dblink->commit();
                    $row["helpers"] = $helpers;
                    $row["helpers_signed"] = $helpers_signed;
                }
                //else ok already signed        
            }
            $result  = new Response($this->operation->operation,ErrorCodes::OK,$row);
            return $result;
        }
    }
    
//TODO needs revising  
    class GiveUpCallHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::GIVE_UP_CALL);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;
            
            if(
                 !( $user->data['role'] == UserRoles::HELPER and 
                    //  $user->data['admitted'] > 0              and //already included in role&rights
                    ( ( $user->data['rights'] & UserRights::URS_REQUEST_SIGN_UP) > 0 ) 
                 ) 
              ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"this operation is unavailible for you. No rights","",__FILE__.__LINE__);
            }
                        
            $this->operation->getArg(0,$reqstump);
            
            $user->get($dblink,1); 
            if( $user->data['current_request'] != $reqstump ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNAVAILIBLE,"you are not signed for this request(1)","",__FILE__.__LINE__);
            }
            
            $reqData = decode_obj_stump($reqstump);
            if( !( $reqData and array_key_exists('user_phone', $reqData) ) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_BAD_DATA,"bad stump data","",__FILE__.__LINE__);
            }
                        
            $user_phone = $reqData['user_phone'];
            $user_phone = trim($user_phone); //user_phone + etc simbols are corrupted by Encrypt/Decrypt bacause they constructed to make url valid stumps etc.
            if( $user_phone[0] != '+'){$user_phone = "+".$user_phone;} //thats why we must restole corrent phone phormat
            $user_phone = PhoneConverter::convert($user_phone);
            $phone = sql_field_string($user_phone);
            
            $requestTable = new TableUsersRequest();
            $query = $requestTable->select("WHERE (user_phone = $phone) LIMIT 1", array('user_name','user_phone','categories','special_categories','helpers_count','helpers_signed','helpers','status','text','stump'));
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get request data(1)","",__FILE__.__LINE__);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if( !$row ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to get request data(2)","",__FILE__.__LINE__);
            }
            $stmt = NULL;
            
            $userid       = "".$user->id;
            $helpers_count = $row["helpers_count"];
            $helpers_signed = $row["helpers_signed"];
            $helpers = $row["helpers"];
            
            $helpers_arr  = explode(",", $helpers);
            $helpers_arr_new = [];
                       
            for($i = 0; $i<count($helpers_arr); $i++) {
                if( $helpers_arr[$i] != $userid ){
                    $helpers_arr_new[] = $helpers_arr[$i];
                }
            }
            
            if( count($helpers_arr) == count($helpers_arr_new) ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_CONDITION_INCONSISTENCY,"you are not signed for this request(2)","",__FILE__.__LINE__);
            }
            else{
                $helpers_signed = count($helpers_arr_new);
                $helpers = implode(",", $helpers_arr_new);
                
                $dblink->beginTransaction();
                
                $query = $requestTable->update("WHERE (user_phone = $phone) LIMIT 1", array('helpers','helpers_signed'), 
                                                                                      array(sql_field_string($helpers),$helpers_signed)
                                              );
                $stmt = prepare_and_execute($dblink, $query, array());
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to sign down(1)","",__FILE__.__LINE__);
                }
                
                $ptable = $user->dataSources[1];
                $query  = $ptable->update("WHERE ( refid = $userid ) LIMIT 1 ",array('current_request'), array( "NULL" ) ); 
                $stmt = prepare_and_execute($dblink, $query, array());
                if( !$stmt ){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to sign down(2)","",__FILE__.__LINE__);
                }
                
                $dblink->commit();
            }       
            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,'');
            return $result;
        }
    }
    
?>
