<?php
    require_once('AppTables.php');
    require_once('PDO/connect.php');
    require_once ('./general/query/query.php');

    class UserTypes
    {
        CONST ADMIN = "admin";
	CONST USER = "user";
        
        CONST ADMIN_TYPE_ID = 1;
        CONST USER_TYPE_ID  = 2;
        
        static $metadata = array( UserTypes::ADMIN => array('id' => UserTypes::ADMIN_TYPE_ID),
                                  UserTypes::USER  => array('id' => UserTypes::USER_TYPE_ID)
                                );
    }
	
    class UserRights
    {
    //user block
        CONST SELF_INFO_VIEW   =   0x1; //0b1
	CONST SELF_INFO_CHANGE =   0x2; //0b10
	CONST SELF_REQUEST_ADD =   0x4; //0b100
	CONST SELF_REQUEST_VIEW =  0x8; //0b1000
	CONST SELF_REQUEST_CHANGE =0x10;//0b10000
	CONST SELF_REQUEST_DELETE =0x20;//0b100000
	CONST USR_BLOCK_RESERV_1  =0x40;//0b1000000 
	CONST USR_BLOCK_RESERV_2  =0x80;//0b10000000
	//observers helpers block
	CONST URS_REQUEST_VIEW =     0x100;
	CONST URS_REQUEST_SIGN_UP =  0x200;
	CONST OH_BLOCK_RESERV_1 =    0x400;
	CONST OH_BLOCK_RESERV_2 =    0x800;
	//admins block p1
	CONST URS_REQUEST_DELETE = 0x1000;
	CONST URS_INFO_VIEW      = 0x2000; //0b1
	CONST URS_INFO_CHANGE =    0x4000; //0b10
	CONST USR_RIGHTS_CHANGE  =    0x8000;
	//admins block p2
	CONST USR_ADD =    0x10000;
	CONST USR_CHANGE = 0x2C000; //obsolete same as URS_INFO_CHANGE|USR_CHANGE_RIGHTS
	CONST USR_DELETE = 0x40000;
	CONST ADM_BLOCK_RESERV_1 = 0x80000;
	
	//sadmin block
	CONST ADM_ADD = 0x100000;
	CONST ADM_INFO_VIEW = 0x200000;
	CONST ADM_INFO_CHANGE = 0x400000;
	CONST ADM_RIGHTS_CHANGE = 0x800000;
	//reserv
	CONST ADM_DELETE = 0x1000000;
	CONST ADM_CHANGE = 0x2C00000;
	CONST RESERV_1 = 0x4000000;
	CONST RESERV_2 = 0x8000000;
	CONST RESERV_3 = 0x10000000;
		
	//standart
	CONST USR_STANDART      = 0x3f;
	CONST OBSERVER_STANDART = 0x13f;
	CONST HELPER_STANDART   = 0x33f;
	CONST ADM_STANDART      = 0xfffff;
        CONST ADM_ADM_STANDART  = 0x1ff00fff;
	CONST SADM_STANDART     = 0x1fffffff;
               
        //masks
        CONST USR_BLOCK_MASK = 0xff;
        CONST OH_BLOCK_MASK  = 0xf00;
        CONST ADM_BLOCK_MASK = 0xff000;
        CONST ADM_ADM_BLOCK_MASK = 0x1ff00000;
        CONST SADM_BLOCK_MASK = 0x1ff00000;
    }
        
    class UserRoles
    {
        CONST NO_ROLE     = 0x0; //  
	CONST ADMIN_ADMIN = 0x1; // specific roles have no separate use but only as part of SUPER_ADMIN role
	CONST ADMIN       = 0x2;
	CONST OBSERVER    = 0x4;       
        CONST UR_ADMIN_RESERVER_1 = 0x8;
        CONST SUPER_ADMIN = 0xF;
        
        CONST USER        = 0x10;
        CONST HELPER      = 0x20;
                
        static public $roles = [
            UserRoles::SUPER_ADMIN,
            UserRoles::ADMIN,
            UserRoles::USER,
            UserRoles::OBSERVER,
            UserRoles::HELPER
                               ];
                
        static public $role_rights_comp = [
                                           UserRoles::SUPER_ADMIN => UserRights::SADM_STANDART ,
                                           UserRoles::ADMIN => UserRights::ADM_STANDART,
                                           UserRoles::USER => UserRights::USR_STANDART,
                                           UserRoles::OBSERVER => UserRights::OBSERVER_STANDART,
                                           UserRoles::HELPER => UserRights::HELPER_STANDART
                                          ];
    }
    
    class UserDataTypes
    {
        CONST MAIL = 1;
        CONST PHONE = 2;
    }
    
    class UserSexs
    {
        CONST NO = 0x0;//UNKNOWN
        
        CONST MAN = 0x1;
        CONST WOMAN = 0x2;
        
        CONST BOTH = 0x3;
    }
    
    abstract class UserObject
    {
        public $id = 0;
        public $type = "";
        public $data = [];
        public $dataSources = [];
                
        function __construct($type, $dataSources) {
            $this->type = $type;
            $this->dataSources = $dataSources;
        }
        
        public function getCoreData($dblink,$login="",$mail="",$phone="")
        {
            $result = NULL;
            $whereClauseEntryes = array();
            if( strlen($login) > 0 ){
                $whereClauseEntryes[] = "login LIKE '$login'";
            }
            if( strlen($mail) > 0 ){
                $whereClauseEntryes[] = "mail LIKE '$mail'";
            }
            if( strlen($phone) > 0 ){
                $whereClauseEntryes[] = "phone LIKE '$phone'";
            }
            
            if( count($whereClauseEntryes) > 0 ){
                $whereClause = sprintf("WHERE %s LIMIT 1",implode("and",$whereClauseEntryes));
                
                $mtable = $this->dataSources[0];
                $query = $mtable->select($whereClause);
                $stmt = prepare_and_execute($dblink, $query, array());
                if($stmt){
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if( $row ){    
                        $this->id = $row['id'];
                        unset( $row['id'] );
                        unset( $row['pass'] );
                        $this->data = array_merge($this->data,$row);
                    }
                    $result = $row;
                }
            }     
            return $result;
        }
        
        public function get($dblink, $depth = PHP_INT_MAX)
        {
            $result = NULL;
            $id = $this->id;
            if( $id > 0 ){
                $depthVal = min( array( $depth, count( $this->dataSources)-1 ) );
                
                $qadds = array();

                $mTableName = $this->dataSources[0]->name;
                $qadds[] = "SELECT * FROM $mTableName as m ";
                for($i = 1; $i <= $depthVal; $i++){
                    $tableName = $this->dataSources[$i]->name;
                    $ninkName = "s$i";
                    $qadds[] = "INNER JOIN $tableName as $ninkName ON m.id = $ninkName.refid ";
                }
                $qadds[] = "WHERE m.id = $id LIMIT 1";
                $query = implode(" ", $qadds);
                $stmt = prepare_and_execute($dblink, $query, array());
                if( $stmt ){
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if( $row ){
                        unset($row['id']);
                        unset($row['pass']);
                        unset($row['refid']);
                        $this->data = array_merge($this->data,$row);
                    }
                    $result = $row;
                } 
            }
            return $result;
        }
        
    }
    
    class Admin extends UserObject
    {
        function __construct() {
            parent::__construct(UserTypes::ADMIN, array(new TableAdmins, new TableAdminsProfiles) );
        } 
    }
     
    class User extends UserObject
    {
        function __construct() {
            parent::__construct(UserTypes::USER, array( new TableUsers, new TableUsersProfiles, new TableUsersLocation() ) );
        }
        
        public function get($dblink,$depth = PHP_INT_MAX)
        {
            $result = NULL;
            $id = $this->id;
            if( $id > 0 ){
//[deprecated] no need in this project                
//                if( !array_key_exists('login',$this->data) ){
//                    //id set externally
//                    $data = parent::get($dblink,0);
//                    if( intval( $data['role'] ) == UserRoles::HELPER ){
//                    //    $this->dataSources[] = new TableHelpersProfiles();
//                        $this->dataSources[] = new TableUsersLocation();
//                    }
//                }
                $result = parent::get($dblink,$depth);
            }
            return $result;
        }
        
        public function getCoreData($dblink,$login="",$mail="",$phone="")
        {
            $result = parent::getCoreData($dblink,$login,$mail,$phone);
//[deprecated] no need in this project
//            $data = $result;
//            if( $data['role'] == UserRoles::HELPER ){
//                //$this->dataSources[] = new TableHelpersProfiles();
//                $this->dataSources[] = new TableUsersLocation();
//            }
            return $result;
        }
    }
    
?>