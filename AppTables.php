<?php
	require_once('sqlCRUD/sqlTable.php');
	
	class UserTypes
	{
		CONST SUPER_ADMIN = "super_admin";
		CONST ADMIN = "admin";
		CONST USER = "user";		
	}
	
	class UserRoles
	{
		CONST SUPER_ADMIN = 0x0;
		CONST ADMIN       = 0x1;
		CONST USER        = 0x2;
		CONST OBSERVER    = 0x4;
		CONST HELPER      = 0x8;
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
		CONST SADM_STANDART      = 0x1fffffff;
	}
	
	class RequestStatus
	{
		CONST OPENED = 0;
		CONST CLOSED = 1;
		CONST EXPIRED = 2;
		CONST DONE   = 3;	
	}
	
	class SessionStatus
	{
		CONST OPENED = 0;
		CONST CLOSED = 1;
		CONST EXPIRED = 2;	
	}
	
	Class FieldsGeneralMeaning
	{
		public static $data = array( "id" => array( "name" => "id",
		                                           "type" => "INT",
		                                           "options" => array("AUTO_INCREMENT","UNIQUE","NOT NULL","PRIMARY KEY"),
		                                           "index" =>""
		                                            ), 
		                                            
		                             "refid" => array( "name" => "refid",
		                                               "type" => "INT",
		                                               "options" => array("UNIQUE","NOT NULL"),
		                                               "index" =>"INDEX refind(refid)"
		                                            ),
		                                            
		                             "login" => array( "name" => "login",
		                                              "type" => "VARCHAR(32)",
		                                              "options" => array("NOT NULL","UNIQUE"),
		                                              "index" =>"INDEX logind(login)"
		                                            ),
		                                            
		                             "pass" => array( "name" => "pass",
		                                             "type" => "VARCHAR(256)",
		                                             "options" => array("NOT NULL","UNIQUE"),
		                                             "index" =>""
		                                            ), 
		                                            
		                             "mail" => array( "name" => "mail",
		                                              "type" => "VARCHAR(64)",
		                                              "options" => array("NOT NULL","UNIQUE"),
		                                              "index" =>"INDEX mailind(mail)"
		                                            ),
		                                            
		                             "phone" => array( "name" => "phone",
		                                              "type" => "VARCHAR(32)",
		                                              "options" => array("NOT NULL","UNIQUE"),
		                                              "index" =>"INDEX phoneind(phone)"
		                                            ),
		                                            
		                             "stump" => array( "name" => "stump",
		                                               "type" => "VARCHAR(512)",
		                                               "options" => array("NOT NULL","UNIQUE"),
		                                               "index" =>"INDEX stumpind(stump)"
		                                            ),
		                                            
		                             "role" => array( "name" => "role",
		                                              "type" =>"INT",
		                                              "options" => array("NOT NULL"),
		                                              "index" =>"INDEX roleind(role)"
		                                            ),
		                                            
		                             "rights" => array( "name" => "rights",
		                                               "type" =>"INT",
		                                               "options" => array("NOT NULL"),
		                                               "index" =>"INDEX rightsind(rights)"
		                                            ),
		                                                             
		                             "date" => array("name"=>"date",
		                                            "type"=>"DATE",
		                                            "options"=>array("NOT NULL","DEFAULT '1800-01-01'"),
		                                            "index" => "INDEX dateind(date)"
		                                            ),
		                             "time" => array("name" => "time",
		                            			    "type" => "TIME",
		                            			    "options"=>array("NOT NULL","DEFAULT '00:00:00'"),
		                            			    "index" => "INDEX timeind(time)" 
		                            			   )
		                           );
	}
			
	//--------Admins------------------------------
	class TableAdmins extends SqlTable
	{
		CONST type = UserTypes::ADMIN;

		public function __construct()
		{			
			parent::__construct( "admins",
			                      array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                           FieldsGeneralMeaning::$data['id']['type'],
			                                           FieldsGeneralMeaning::$data['id']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['login']['name'],
			                                           FieldsGeneralMeaning::$data['login']['type'],
			                                           FieldsGeneralMeaning::$data['login']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['pass']['name'],
			                                           FieldsGeneralMeaning::$data['pass']['type'],
			                                           FieldsGeneralMeaning::$data['pass']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['mail']['name'],
			                                           FieldsGeneralMeaning::$data['mail']['type'],
			                                           FieldsGeneralMeaning::$data['mail']['options']
			                                           ),
			                              new SqlField('mail_confirmed',
			                                           'INT',
			                                           array("DEFAULT 0")
			                                           ),             
			                              new SqlField(FieldsGeneralMeaning::$data['phone']['name'],
			                                           FieldsGeneralMeaning::$data['phone']['type'],
			                                           FieldsGeneralMeaning::$data['phone']['options']
			                                           ),
			                              new SqlField('phone_confirmed',
			                                           'INT',
			                                           array("DEFAULT 0")
			                                           ),             
			                              new SqlField(FieldsGeneralMeaning::$data['role']['name'],
			                                           FieldsGeneralMeaning::$data['role']['type'],
			                                           FieldsGeneralMeaning::$data['role']['options']
			                                          ),
			                              new SqlField(FieldsGeneralMeaning::$data['rights']['name'],
			                                           FieldsGeneralMeaning::$data['rights']['type'],
			                                           FieldsGeneralMeaning::$data['rights']['options']
			                                           ),             
			                              new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                           FieldsGeneralMeaning::$data['stump']['type'],
			                                           FieldsGeneralMeaning::$data['stump']['options']
			                                           )
			                            ),             
	                           	  array(FieldsGeneralMeaning::$data['login']['index'],
	                    		        FieldsGeneralMeaning::$data['mail']['index'],
	                    		        FieldsGeneralMeaning::$data['phone']['index'],
	                    		        FieldsGeneralMeaning::$data['role']['index'],
			                            FieldsGeneralMeaning::$data['rights']['index'],
	                    		        FieldsGeneralMeaning::$data['stump']['index']),
	                    		  "ENGINE=INNODB" );		
		}		
	}
	
	class TableAdminsProfiles extends SqlTable
	{
		CONST type = UserTypes::ADMIN;
		
		public function __construct()
		{
			parent::__construct( "admins_profiles",
			                     array( new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                           ),
			                            new SqlField( "name","VARCHAR(32)",array()  ),
			                            new SqlField( "sName","VARCHAR(32)",array() ),
			                            new SqlField( "fName","VARCHAR(32)",array() ),
			                            new SqlField( "birth",
			                                          FieldsGeneralMeaning::$data['date']['type'],
			                                          FieldsGeneralMeaning::$data['date']['options'] 
			                                        ),
			                            new SqlField( "birth_time", 
			                                          FieldsGeneralMeaning::$data['time']['type'],
			                                          FieldsGeneralMeaning::$data['time']['options']
			                                        )
			                          ),
			                     array(FieldsGeneralMeaning::$data['refid']['index']
			                      ),
			                     "ENGINE=INNODB" 
			                   );
		}
	}
	
	//-----------Users------------------------------------------------------------------------
	class TableUsers extends SqlTable
	{
		CONST type = UserTypes::USER;
		
		public function __construct()
		{
			parent::__construct( "users",
			                      array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                           FieldsGeneralMeaning::$data['id']['type'],
			                                           FieldsGeneralMeaning::$data['id']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['login']['name'],
			                                           FieldsGeneralMeaning::$data['login']['type'],
			                                           FieldsGeneralMeaning::$data['login']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['pass']['name'],
			                                           FieldsGeneralMeaning::$data['pass']['type'],
			                                           FieldsGeneralMeaning::$data['pass']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['mail']['name'],
			                                           FieldsGeneralMeaning::$data['mail']['type'],
			                                           FieldsGeneralMeaning::$data['mail']['options']
			                                           ),
			                              new SqlField('mail_confirmed',
			                                           'INT',
			                                           array("DEFAULT 0")
			                                           ),             
			                              new SqlField(FieldsGeneralMeaning::$data['phone']['name'],
			                                           FieldsGeneralMeaning::$data['phone']['type'],
			                                           FieldsGeneralMeaning::$data['phone']['options']
			                                           ),
			                              new SqlField('phone_confirmed',
			                                           'INT',
			                                           array("DEFAULT 0")
			                                           ),            
			                              new SqlField(FieldsGeneralMeaning::$data['role']['name'],
			                                           FieldsGeneralMeaning::$data['role']['type'],
			                                           FieldsGeneralMeaning::$data['role']['options']
			                                           ),
			                              new SqlField(FieldsGeneralMeaning::$data['rights']['name'],
			                                           FieldsGeneralMeaning::$data['rights']['type'],
			                                           FieldsGeneralMeaning::$data['rights']['options']
			                                           ), 
			                              new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                           FieldsGeneralMeaning::$data['stump']['type'],
			                                           FieldsGeneralMeaning::$data['stump']['options']
			                                           )
			                            ),             
	                           	  array(FieldsGeneralMeaning::$data['login']['index'],
	                    		        FieldsGeneralMeaning::$data['mail']['index'],
	                    		        FieldsGeneralMeaning::$data['phone']['index'],
	                    		        FieldsGeneralMeaning::$data['role']['index'],
			                            FieldsGeneralMeaning::$data['rights']['index'],
	                    		        FieldsGeneralMeaning::$data['stump']['index']),
	                    		  "ENGINE=INNODB" );		
		}		
	}
	
	class TableUsersProfiles extends SqlTable
	{
		CONST type = UserTypes::USER;
		
		public function __construct()
		{
			parent::__construct( "users_profiles",
			                     array( new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                           ),
			                            new SqlField( "name","VARCHAR(32)",array()  ),
			                            new SqlField( "sName","VARCHAR(32)",array() ),
			                            new SqlField( "fName","VARCHAR(32)",array() ),
			                            new SqlField( "birth",
			                                          FieldsGeneralMeaning::$data['date']['type'],
			                                          FieldsGeneralMeaning::$data['date']['options'] 
			                                        ),
			                            new SqlField( "birth_time", 
			                                          FieldsGeneralMeaning::$data['time']['type'],
			                                          FieldsGeneralMeaning::$data['time']['options']
			                                        ),
			                            
			                           ),
			                     array(FieldsGeneralMeaning::$data['refid']['index']),
			                     "ENGINE=INNODB" 
			                   );
		}
	}
	
	class TableObserversProfiles extends SqlTable
	{
		public function __construct()
		{
			parent::__construct("observers_profiles",
								array( new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                        FieldsGeneralMeaning::$data['refid']['type'],
			                                        FieldsGeneralMeaning::$data['refid']['options']
			                                       ),
									   new SqlField( "field","TEXT", array()) 
							    ),
								array( FieldsGeneralMeaning::$data['refid']['index'],
								       'FULLTEXT(field)'
								     ),
								"ENGINE=INNODB"
								);
		}
	}
		
	class TableHelpersProfiles extends SqlTable
	{
		public function __construct()
		{
			parent::__construct( "helpers_profiles",
			                     array( new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                       ),
			                            new SqlField( "gratitude", "INT", array() ) 
			                          ),
			                     array(  FieldsGeneralMeaning::$data['refid']['index'] ),
			                     "ENGINE=INNODB"
			                    );
		}
	}
	
	//---------Sessions---------------------------------------------------------------
	class TableAdmSessions extends SqlTable
	{
		CONST type = UserTypes::ADMIN;
		
		public function __construct()
		{
			parent::__construct( "adm_sessions",
								 array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                         FieldsGeneralMeaning::$data['id']['type'],
			                                         FieldsGeneralMeaning::$data['id']['options']
			                                        ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                       ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
                                                     FieldsGeneralMeaning::$data['stump']['type'],
		                                             FieldsGeneralMeaning::$data['stump']['options']
			                                        )
								 ),
								 array( FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index']
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
	
	class TableUsrSessions extends SqlTable
	{
		CONST type = UserTypes::USER;
		
		public function __construct()
		{
			parent::__construct( "usr_sessions",
								 array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                         FieldsGeneralMeaning::$data['id']['type'],
			                                         FieldsGeneralMeaning::$data['id']['options']
			                                        ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                       ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
		                                             FieldsGeneralMeaning::$data['stump']['type'],
		                                             FieldsGeneralMeaning::$data['stump']['options']
			                                        )
								 ),
								 array( FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index']
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
	
	//--------Requests-------------------------------------------------------------------------
	class TableRegionRequest extends SqlTable
	{
		public $region = 0;
		
		public function __construct($region = 0)
		{
			$this->region = $region;
			parent::__construct( "requests_region_$region",
								 array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                         FieldsGeneralMeaning::$data['id']['type'],
			                                         FieldsGeneralMeaning::$data['id']['options']
			                                        ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                        ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("")),
								        new SqlField("sh","DOUBLE",array("NOT NULL") ),
								        new SqlField("dl","DOUBLE",array("NOT NULL") ),
								        new SqlField("cats","INT", array("NOT NULL") ),
								        new SqlField("specats","TEXT",array() ),
								        new SqlField("helpers_req","INT",array("NOT NULL") ),
								        new SqlField("helpers_signed","INT",array() ),
										new SqlField("helpers","TEXT",array() ),
										new SqlField("status","INT",array() ),
										new SqlField("reward","INT",array("NOT NULL") ),
										new SqlField("text","TEXT",array() ),
										new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                         FieldsGeneralMeaning::$data['stump']['type'],
			                                         FieldsGeneralMeaning::$data['stump']['options']
			                                        )								        
								 ),
								 array( FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index'],
								        "INDEX catind(cats)",
								        "INDEX statind(status)",
								        "FULLTEXT(specats,helpers,text)"
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
		
	class TableHelpersRequests extends SqlTable
	{
		public $id = 0;
		
		public function __construct($id = 0)
		{
			$this->id = $id;
			parent::__construct( "helpers_requests",
								 array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                         FieldsGeneralMeaning::$data['id']['type'],
			                                         FieldsGeneralMeaning::$data['id']['options']
			                                       ),
								 		new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                       ),
								        new SqlField("region","INT",array("NOT NULL") ),
								        new SqlField("req_id","INT", array("NOT NULL") ),
								        new SqlField("req_stump",FieldsGeneralMeaning::$data['stump']['type'],array("NOT NULL")),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField("cats","INT", array("NOT NULL") ),
								        new SqlField("specats","TEXT",array() ),
								        new SqlField("status","INT",array() ),
										new SqlField("reward","INT",array("NOT NULL") ),
										new SqlField("bonus_reward","INT",array() )								        
								 ),
								 array( FieldsGeneralMeaning::$data['refid']['index'],
								        "INDEX reqind(region,req_id) "
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
	
	//-------------Data Confirmation--------------------------------------
	class TableUserDataConfirmation extends SqlTable
	{
		public function __construct()
		{
			parent::__construct("user_data_confirmation",
			                    array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                        FieldsGeneralMeaning::$data['id']['type'],
			                                        FieldsGeneralMeaning::$data['id']['options']
			                                        ),
			                           new SqlField('usrtype',
			                                        'INT',
			                                        array("NOT NULL")
			                                        ),
			                           new SqlField('refid',
			                                         'INT',
			                                         array("NOT NULL")
			                                        ),            
			                           new SqlField('datatype',
			                                        'INT',
			                                        array("NOT NULL")
			                                         ),
			                           new SqlField('time',
			                                        'DATETIME',
			                                        array("NOT NULL","DEFAULT NOW()")
			                                       ),
			                           new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                        FieldsGeneralMeaning::$data['stump']['type'],
			                                        FieldsGeneralMeaning::$data['stump']['options']
			                                        )
			                         ),             
	                           	  array('INDEX refind(refid)',
	                           	  		FieldsGeneralMeaning::$data['stump']['index']
	                           	  	   ),
	                    		  "ENGINE=INNODB");
		}
	}
	
	//-----------History---------------------------------
	class TableOperationsHistory extends SqlTable
	{
		public function __construct()
		{
			parent::__construct("operations_history",
			                    array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                                         FieldsGeneralMeaning::$data['id']['type'],
			                                         FieldsGeneralMeaning::$data['id']['options']
			                                        ),
			                            new SqlField('refid',
			                                         'INT',
			                                         array()
			                                        ),            
			                            new SqlField('operation',
			                                         'VARCHAR(128)',
			                                         array("NOT NULL")
			                                         ),
			                            new SqlField('time',
			                                         'DATETIME',
			                                         array("NOT NULL","DEFAULT NOW()")
			                                         ),
			                            new SqlField('retCode',
			                                         'INT',
			                                         array("NOT NULL")
			                                         ),
			                            new SqlField('result',
			                                         'VARCHAR(512)',
			                                         array("NOT NULL")
			                                         ),             
			                            new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                         FieldsGeneralMeaning::$data['stump']['type'],
			                                         FieldsGeneralMeaning::$data['stump']['options']
			                                        )
			                         ),             
	                           	  array('INDEX refind(refid)',
	                           	  		'INDEX opind(operation)',
	                           	  		'INDEX timeind(time)'
	                           	  	   ),
	                    		  "ENGINE=INNODB");
		}
	}
	
	class TableAdmSessionsHistory extends SqlTable
	{
		CONST type = UserTypes::ADMIN;
		
		public function __construct()
		{
			parent::__construct( "adm_sessions_history",
								 array( new SqlField('id',
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                        ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         array("NOT NULL")
			                                       ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                         FieldsGeneralMeaning::$data['stump']['type'],
			                                         FieldsGeneralMeaning::$data['stump']['options']
			                                        ),
			                            new SqlField('status',
			                                         'INT',
			                                         array("NOT NULL")
			                                        )
								 ),
								 array( 'INDEX idind(id)',
								        FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index'],
								        'INDEX statind(status)'
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
	
	class TableUsrSessionsHistory extends SqlTable
	{
		CONST type = UserTypes::USER;
		
		public function __construct()
		{
			parent::__construct( "usr_sessions_history",
								 array( new SqlField('id',
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         FieldsGeneralMeaning::$data['refid']['options']
			                                        ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         array("NOT NULL")
			                                       ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                         FieldsGeneralMeaning::$data['stump']['type'],
			                                         FieldsGeneralMeaning::$data['stump']['options']
			                                        ),
			                            new SqlField('status',
			                                         'INT',
			                                         array("NOT NULL")
			                                        )
								 ),
								 array( 'INDEX idind(id)',
								        FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index'],
								        'INDEX statind(status)'
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}	
	
	class TableRegionRequestHistory extends SqlTable
	{
		public $region = 0;
		
		public function __construct($region = 0)
		{
			$this->region = $region;
			parent::__construct( "requests_region_history_$region",
								 array( new SqlField("req_id",FieldsGeneralMeaning::$data['refid']['type'],FieldsGeneralMeaning::$data['refid']['options'] ),
								        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                                         FieldsGeneralMeaning::$data['refid']['type'],
			                                         array("NOT NULL")
			                                       ),
								        new SqlField("otime","DATETIME",array("NOT NULL")),
								        new SqlField("ctime","DATETIME",array("NOT NULL")),
								        new SqlField("sh","DOUBLE",array("NOT NULL") ),
								        new SqlField("dl","DOUBLE",array("NOT NULL") ),
								        new SqlField("cats","INT", array("NOT NULL") ),
								        new SqlField("specats","TEXT",array() ),
								        new SqlField("helpers_req","INT",array("NOT NULL") ),
								        new SqlField("helpers_signed","INT",array() ),
										new SqlField("helpers","TEXT",array() ),
										new SqlField("status","INT",array() ),
										new SqlField("reward","INT",array("NOT NULL") ),
										new SqlField("text","TEXT",array() ),
										new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                                         FieldsGeneralMeaning::$data['stump']['type'],
			                                         FieldsGeneralMeaning::$data['stump']['options']
			                                        ),
										new SqlField("comment","TEXT",array() )								        
								 ),
								 array( FieldsGeneralMeaning::$data['refid']['index'],
								        FieldsGeneralMeaning::$data['stump']['index'],
								        "INDEX catind(cats)",
								        "INDEX statind(status)",
								        "FULLTEXT(specats,helpers,text,comment)"
								 ),
								 "ENGINE=INNODB"
			  				   );
		}
	}
	
	class TableRegions extends SqlTable
	{
		public function __construct(){
			parent::__construct("regions",
								 array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
								                     FieldsGeneralMeaning::$data['id']['type'],
								                     FieldsGeneralMeaning::$data['id']['options'] ),
								        new SqlField('region',
								                     'INT',
								                     array('NOT NULL')
								                    )
								 ),
								 array( "INDEX regind(region)"
								 ),
								 "ENGINE=INNODB");
		}
	}
	
?>