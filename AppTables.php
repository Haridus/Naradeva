<?php
    require_once('sqlCRUD/sqlTable.php');    
    	
    Class FieldsGeneralMeaning
    {
	public static $data = array("id" => array("name" => "id",
                                                  "type" => "INT",
	                                          "options" => array("AUTO_INCREMENT","UNIQUE","NOT NULL","PRIMARY KEY"),
	                                          "index" =>""
	                                          ),           
	                            "refid" => array("name" => "refid",
		                                     "type" => "INT",
		                                     "options" => array("UNIQUE","NOT NULL"),
		                                     "index" =>"INDEX refind(refid)"
		                                    ),        
		                    "login" => array("name" => "login",
		                                     "type" => "VARCHAR(32)",
		                                     "options" => array("NOT NULL","UNIQUE"),
		                                     "index" =>"INDEX logind(login)"
		                                    ),                        
		                    "pass" => array("name" => "pass",
		                                    "type" => "VARCHAR(256)",
		                                    "options" => array("NOT NULL"),
		                                    "index" =>""
		                                    ),        
		                    "mail" => array("name" => "mail",
		                                    "type" => "VARCHAR(128)",
		                                    "options" => array("NOT NULL","UNIQUE"),
		                                    "index" =>"INDEX mailind(mail)"
		                                   ),                        
		                    "phone" => array("name" => "phone",
		                                     "type" => "VARCHAR(32)",
		                                     "options" => array("NOT NULL","UNIQUE"),
		                                     "index" =>"INDEX phoneind(phone)"
		                                    ),
		                    "stump" => array("name" => "stump",
		                                     "type" => "VARCHAR(512)",
		                                     "options" => array("NOT NULL","UNIQUE"),
		                                     "index" =>"INDEX stumpind(stump)"
		                                    ),
		                                            
		                    "role" => array("name" => "role",
		                                    "type" =>"INT",
		                                    "options" => array("NOT NULL"),
		                                    "index" =>"INDEX roleind(role)"
		                                   ),
		                                            
		                    "rights" => array("name" => "rights",
		                                      "type" =>"INT",
		                                      "options" => array("NOT NULL"),
		                                      "index" =>"INDEX rightsind(rights)"
		                                     ),
		                    "date" => array("name"=>"date",
		                                    "type"=>"DATE",
		                                    "options"=>array(),//"NOT NULL","DEFAULT '1800-01-01'"
		                                    "index" => "INDEX dateind(date)"
		                                   ),
		                    "time" => array("name" => "time",
		                                    "type" => "TIME",
		                                    "options"=>array(),//"NOT NULL","DEFAULT '00:00:00'"
		                                    "index" => "INDEX timeind(time)" 
		                          	   ),
                                    "region_section" => array("name" => "region_section",
                                                              "type" => "TINYINT",
                                                              "options"=>array(),//"NOT NULL"
                                                              "index" => "INDEX rsind(time)" 
                                                             ),
                                    "region" => array("name"   => "region",
                                                      "type"   => "BIGINT",
                                                      "options"=> array(),//"NOT NULL"
                                                      "index"  => "INDEX regionInd(region)" 
                                                     ),
                                    "locale" => array("name"   => "locale",
                                                      "type"   => "VARCHAR(16)",
                                                      "options"=> array("NOT NULL","DEFAULT '".AppSettings::defaultLocale."'"),
                                                      "index"  => "INDEX localeind(locale)" 
                                                     )
                                    );
    }
    
    //--------------------------------------------------------------------------
    class SQLAppTable extends SqlTable
    {
        public $prefix = "";
        public $suffix = "";
        public $baseName = "";
        
        public function __construct($name, $fields, $internalOptions, $externalOptions) {
            parent::__construct($name, $fields, $internalOptions, $externalOptions);
            
            $this->prefix = AppSettings::appDbTablesPrefix;
            $this->suffix = AppSettings::appDbTablesSuffix;
            $this->baseName = $this->name;
            
            $nameEntryes = array();
            if( strlen($this->prefix) > 0 ){
                $nameEntryes[] = $this->prefix;
            }
            $nameEntryes[] = $this->baseName;
            if( strlen($this->suffix) > 0 ){
                $nameEntryes[] = $this->suffix;
            }
            
            $this->name = implode("_", $nameEntryes); 
        }
    }
			
	//--------Admins------------------------------
    class TableAdmins extends SQLAppTable
    {
	CONST type = UserTypes::ADMIN;

        public function __construct()
	{			
            parent::__construct( "admins",
	                         array( new SqlField(FieldsGeneralMeaning::$data['id']['name'],
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
                                        new SqlField('last_visit',
                                                     'DATETIME',
                                                     array('NOT NULL','DEFAULT NOW() ON UPDATE NOW()')
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
	                    	    "ENGINE=INNODB" 
                            );		
		}		
	}
	
    class TableAdminsProfiles extends SQLAppTable
    {
	CONST type = UserTypes::ADMIN;
		
    	public function __construct()
	{
            $ptable = new TableAdmins();
            $ptableName = $ptable->name;
            parent::__construct( "admins_profiles",
	                        array( new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
                                                    FieldsGeneralMeaning::$data['refid']['type'],
                                                    FieldsGeneralMeaning::$data['refid']['options']
                                                    ),
		                        new SqlField("name","VARCHAR(32)",array()  ),
		                        new SqlField("sName","VARCHAR(32)",array() ),
		                        new SqlField("fName","VARCHAR(32)",array() ),
		                        new SqlField("birth",
		                                     FieldsGeneralMeaning::$data['date']['type'],
		                                     FieldsGeneralMeaning::$data['date']['options'] 
		                                    ),
		                        new SqlField("birth_time", 
		                                     FieldsGeneralMeaning::$data['time']['type'],
		                                     FieldsGeneralMeaning::$data['time']['options']
		                                    ),
                                        new SqlField("birth_place", 
		                                     "TEXT",
		                                     array()
		                                    ),
                                        new SqlField(FieldsGeneralMeaning::$data['locale']['name'], 
		                                     FieldsGeneralMeaning::$data['locale']['type'],
		                                     FieldsGeneralMeaning::$data['locale']['options']
		                                    ),
                                        new SqlField("docType", 
		                                     "TINYINT",
		                                     array("DEFAULT 0")
		                                    ),
                                        new SqlField("docNum", 
		                                     "VARCHAR(64)",
		                                     array()
		                                    ),
                                        new SqlField("docInfo", 
		                                     "TEXT",
		                                     array()
		                                    )
                                        ),
                                array("FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE ",
                                      FieldsGeneralMeaning::$data['refid']['index'],
                                      FieldsGeneralMeaning::$data['locale']['index']
                                     ),
		                "ENGINE=INNODB" 
			        );
	}
    }
	
	//-----------Users------------------------------------------------------------------------
    class TableUsers extends SQLAppTable
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
                                        new SqlField('last_visit',
                                                     'DATETIME',
                                                     array('NOT NULL','DEFAULT NOW() ON UPDATE NOW()')
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
	                    	"ENGINE=INNODB" 
                                );		
	}		
    }
	
    class TableUsersProfiles extends SQLAppTable
    {
	CONST type = UserTypes::USER;
		
	public function __construct()
	{
            $ptable = new TableUsers();
            $ptableName = $ptable->name;
            parent::__construct("users_profiles",
                                array(  new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
                                                     FieldsGeneralMeaning::$data['refid']['type'],
                                                     FieldsGeneralMeaning::$data['refid']['options']
			                            ),
                                        new SqlField("name", "VARCHAR(32)",array()  ),
                                        new SqlField("sName","VARCHAR(32)",array() ),
                                        new SqlField("fName","VARCHAR(32)",array() ),
                                        new SqlField("birth",
                                                     FieldsGeneralMeaning::$data['date']['type'],
                                                     FieldsGeneralMeaning::$data['date']['options'] 
                                                    ),
                                        new SqlField("birth_time", 
                                                     FieldsGeneralMeaning::$data['time']['type'],
                                                     FieldsGeneralMeaning::$data['time']['options']
                                                    ),
                                        new SqlField("birth_place", 
                                                     "TEXT",
                                                     array()
                                                    ),
                                        new SqlField("sex",  "TINYINT",array("NOT NULL","DEFAULT 0")  ),
                                        new SqlField(FieldsGeneralMeaning::$data['locale']['name'], 
		                                     FieldsGeneralMeaning::$data['locale']['type'],
		                                     FieldsGeneralMeaning::$data['locale']['options']
		                                    ),
                                        new SqlField("docType", 
		                                     "TINYINT",
		                                     array("DEFAULT 0")
		                                    ),
                                        new SqlField("docNum", 
		                                     "VARCHAR(64)",
		                                     array()
		                                    ),
                                        new SqlField("docInfo", 
		                                     "TEXT",
		                                     array()
		                                    ),
                                        new SqlField("docImage", //reserved for future user...maybe
		                                     "BLOB",
		                                     array("DEFAULT NULL")
		                                    ),
                                        new SqlField("want_help",
			                             "TINYINT",
			                             array("DEFAULT 0")
			                            ),
                                        new SqlField("checked",
			                             "TINYINT",
			                             array("DEFAULT 0")
			                            ), 
                                        new SqlField("admitted",
			                             "TINYINT",
			                             array("DEFAULT 0")
			                            ),
                                        new SqlField("current_request", //for helpers
			                             FieldsGeneralMeaning::$data['stump']['type'],
			                             array("DEFAULT NULL")
			                            )
			            ),
			            array("FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE ",
                                          FieldsGeneralMeaning::$data['refid']['index'],
                                          FieldsGeneralMeaning::$data['locale']['index']
                                        ),
			            "ENGINE=INNODB" 
			        );
	}
    }
	       
    //---------Location---------------------------------------------------------
    class TableUsersLocation extends SQLAppTable
    {
        public function __construct()
        {
            $ptable = new TableUsers();
            $ptableName = $ptable->name;
            parent::__construct("users_location",
                                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
                                                     FieldsGeneralMeaning::$data['id']['type'],
		                                     FieldsGeneralMeaning::$data['id']['options']
		                                    ),
                                        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                             FieldsGeneralMeaning::$data['refid']['type'],
			                             FieldsGeneralMeaning::$data['refid']['options']
			                            ),
                                        new SqlField('time',
                                                     'DATETIME',
                                                     array('NOT NULL','DEFAULT NOW() ON UPDATE NOW()')
                                                    ),
                                        new SqlField('sh',
			                             'DOUBLE',
			                             array()
			                            ),
                                        new SqlField('dl',
			                             'DOUBLE',
			                             array()
			                            ),
                                        new SqlField('region_section_0',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_1',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_2',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_3',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_4',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region',
			                             'BIGINT',
			                             array()
			                            ),
                                        new SqlField('state',
                                                     'INT',
                                                     array())
                                        ),
                                array("FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE ",
                                      FieldsGeneralMeaning::$data['refid']['index'],
                                      "INDEX rsind(region_section_0,region_section_1,region_section_2,region_section_3,region_section_4)",
                                      "INDEX regind(region)",
                                      "INDEX stateind(state)"
                                    ),
                                "ENGINE=INNODB"
                    );
        }
    }
   
    class TableUserLocationHistory extends SQLAppTable
    {
        public $id = NULL;
            
        public function __construct($id)
        {
            $this->id = $id;
            parent::__construct("user_location_history_$id",
                                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
		                                     FieldsGeneralMeaning::$data['id']['type'],
			                             FieldsGeneralMeaning::$data['id']['options']
                                                    ),
                                        new SqlField('time',
                                                     'DATETIME',
                                                     array('UNIQUE','NOT NULL','DEFAULT NOW()')
                                                    ),
                                        new SqlField('sh',
			                             'DOUBLE',
			                             array()
			                            ),
                                        new SqlField('dl',
			                             'DOUBLE',
			                             array()
			                            ),
                                        new SqlField('region_section_0',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_1',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_2',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_3',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region_section_4',
                                                     'TINYINT',
                                                     array()
                                                    ),
                                        new SqlField('region',
			                             'INT',
			                             array()
			                            ),
                                        new SqlField('state',
                                                     'INT',
                                                     array()
                                                    )
                                ),
                                array("INDEX rsind(region_section_0,region_section_1,region_section_2,region_section_3,region_section_4)",
                                      "INDEX regind(region)",
                                      "INDEX stateind(state)"
                                    ),
                                "ENGINE=INNODB"
                        );
        }
    }
    
    //---------Sessions---------------------------------------------------------------
    class TableAdmSessions extends SQLAppTable
    {
	CONST type = UserTypes::ADMIN;
		
	public function __construct()
	{
            $ptable = new TableAdmins();
            $ptableName = $ptable->name;
            parent::__construct( "adm_sessions",
                                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
                                                     FieldsGeneralMeaning::$data['id']['type'],
			                             FieldsGeneralMeaning::$data['id']['options']
			                            ),
                                        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                             FieldsGeneralMeaning::$data['refid']['type'],
			                             FieldsGeneralMeaning::$data['refid']['options']
			                            ),
                                        new SqlField("otime","DATETIME",array("NOT NULL","DEFAULT NOW()")),
                                        new SqlField("ctime","DATETIME",array("NOT NULL")),
                                        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
                                                     FieldsGeneralMeaning::$data['stump']['type'],
                                                     FieldsGeneralMeaning::$data['stump']['options']
			                            )
                                    ),
                                array( "FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE ",
                                        FieldsGeneralMeaning::$data['refid']['index'],
                                        FieldsGeneralMeaning::$data['stump']['index']
                                    ),
                                "ENGINE=INNODB"
                                );
        }
    }
	
    class TableUsrSessions extends SQLAppTable
    {
	CONST type = UserTypes::USER;
		
	public function __construct()
	{
            $ptable = new TableUsers();
            $ptableName = $ptable->name;
            parent::__construct( "usr_sessions",
                                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
                                                     FieldsGeneralMeaning::$data['id']['type'],
		                                     FieldsGeneralMeaning::$data['id']['options']
		                                    ),
                                        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
		                                     FieldsGeneralMeaning::$data['refid']['type'],
		                                     FieldsGeneralMeaning::$data['refid']['options']
		                                    ),
					new SqlField("otime","DATETIME",array("NOT NULL","DEFAULT NOW()")),
					new SqlField("ctime","DATETIME",array("NOT NULL")),
					new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
		                                     FieldsGeneralMeaning::$data['stump']['type'],
		                                     FieldsGeneralMeaning::$data['stump']['options']
		                                    )
                                        ),
                                array( "FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE ",
                                        FieldsGeneralMeaning::$data['refid']['index'],
                                        FieldsGeneralMeaning::$data['stump']['index']
                                     ),
                                "ENGINE=INNODB"
                                );
	}
    }
	
    //--------Requests-------------------------------------------------------------------------
    class TableUsersRequest extends SQLAppTable
    {
        public function __construct()
        {
            parent::__construct("users_requests",
                                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
                                                     FieldsGeneralMeaning::$data['id']['type'],
			                             FieldsGeneralMeaning::$data['id']['options']
			                            ),
                                        new SqlField(FieldsGeneralMeaning::$data['refid']['name'], //may be empty
			                             FieldsGeneralMeaning::$data['refid']['type'],
			                             array("DEFAULT NULL")
			                            ),
					new SqlField('user_name', 
                                                     'VARCHAR(32)', 
                                                     array("NOT NULL") 
                                                    ),
                                        new SqlField('user_phone',
			                             FieldsGeneralMeaning::$data['phone']['type'],
			                             FieldsGeneralMeaning::$data['phone']['options']
			                            ),
                                        new SqlField('categories',
			                             'BIGINT',
			                             array("DEFAULT '0'")
			                            ),
                                        new SqlField('special_categories',
			                             'TEXT',
			                             array()
			                            ),
                                        new SqlField('helpers_count',
			                             'TINYINT',
			                             array("DEFAULT 1")
			                            ),
                                        new SqlField('helpers_signed',
			                             'TINYINT',
			                             array("DEFAULT 0")
			                            ),
                                        new SqlField('helpers',
			                             'TEXT',
			                             array()
			                            ),
                                        new SqlField( "otime",
                                                      "DATETIME",
                                                      array("NOT NULL", "DEFAULT NOW()") 
                                                    ),
					new SqlField( "ctime",
                                                      "DATETIME",
                                                      array() 
                                                    ),
					new SqlField( "sh",
                                                      "DOUBLE",
                                                      array("NOT NULL") 
                                                    ),
					new SqlField( "dl",
                                                      "DOUBLE",
                                                      array("NOT NULL")
                                                    ),
                                        new SqlField('region_section_0',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_1',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_2',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_3',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_4',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region',
			                             'BIGINT',
			                             array("NOT NULL")
			                            ),  
                                        new SqlField("reward",
                                                     "INT",
                                                     array("DEFAULT NULL") 
                                                    ),
                                        new SqlField("status",
                                                     "TINYINT",
                                                     array("DEFAULT 0") 
                                                    ),
                                        new SqlField("text",
                                                     "TEXT",
                                                     array() 
                                                    ),
					new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                             FieldsGeneralMeaning::$data['stump']['type'],
			                             FieldsGeneralMeaning::$data['stump']['options']
			                )								        
				    ),
				array(  FieldsGeneralMeaning::$data['refid']['index'],
                                        "INDEX phoneind(user_phone)",
                                        "INDEX rsind(region_section_0,region_section_1,region_section_2,region_section_3,region_section_4)",
                                        "INDEX regind(region)",
                                        FieldsGeneralMeaning::$data['stump']['index'],
                                        "FULLTEXT(text)",
                                        "FULLTEXT(special_categories)"
				),
				"ENGINE=INNODB"
                        );
        }
    }
        
    class TableUsersRequestHistory extends SQLAppTable
    {
        public function __construct()
        {
            parent::__construct("users_requests_history",
                                array(  new SqlField('reqid',
                                                    FieldsGeneralMeaning::$data['refid']['type'],
                                                    FieldsGeneralMeaning::$data['refid']['options']
                                                   ),
                                        new SqlField(FieldsGeneralMeaning::$data['refid']['name'],
			                             FieldsGeneralMeaning::$data['refid']['type'],
			                             array("DEFAULT NULL")
			                            ),
					new SqlField('user_name', 
                                                     'VARCHAR(32)', 
                                                     array("NOT NULL") 
                                                    ),
                                        new SqlField('user_phone',
			                             FieldsGeneralMeaning::$data['phone']['type'],
			                             array("NOT NULL")
			                            ),
                                        new SqlField('categories',
			                             'BIGINT',
			                             array("DEFAULT '0'")
			                            ),   
                                        new SqlField('special_categories',
			                             'TEXT',
			                             array()
			                            ),
                                        new SqlField('helpers_count',
			                             'TINYINT',
			                             array("DEFAULT NULL")
			                            ),
                                        new SqlField('helpers_signed',
			                             'TINYINT',
			                             array("DEFAULT NULL")
			                            ),
                                        new SqlField('helpers',
			                             'TEXT',
			                             array("DEFAULT NULL")
			                            ),
                                        new SqlField( "otime",
                                                      "DATETIME",
                                                      array("NOT NULL", "DEFAULT NOW()") 
                                                    ),
					new SqlField( "ctime",
                                                      "DATETIME",
                                                      array() 
                                                    ),
					new SqlField( "sh",
                                                      "DOUBLE",
                                                      array("NOT NULL") 
                                                    ),
					new SqlField( "dl",
                                                      "DOUBLE",
                                                      array("NOT NULL")
                                                    ),
                                        new SqlField('region_section_0',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_1',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_2',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_3',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region_section_4',
                                                     'TINYINT',
                                                     array("")
                                                    ),
                                        new SqlField('region',
			                             'BIGINT',
			                             array("NOT NULL")
			                            ),
                                        new SqlField("reward",
                                                     "INT",
                                                     array("DEFAULT NULL") 
                                                    ),
                                        new SqlField("grade",
                                                     "INT",
                                                     array("DEFAULT NULL") 
                                                    ),
                                        new SqlField("status",
                                                     "TINYINT",
                                                     array("DEFAULT 0") 
                                                    ),
                                        new SqlField("text",
                                                     "TEXT",
                                                     array() 
                                                    ),
                                        new SqlField("comment",
                                                     "TEXT",
                                                     array() 
                                                    ),
					new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
			                             FieldsGeneralMeaning::$data['stump']['type'],
			                             FieldsGeneralMeaning::$data['stump']['options']
			                )								        
                                    ),
				array(  FieldsGeneralMeaning::$data['refid']['index'],
                                        "INDEX phoneind(user_phone)",
                                        "INDEX rsind(region_section_0,region_section_1,region_section_2,region_section_3,region_section_4)",
                                        "INDEX regind(region)",
                                        FieldsGeneralMeaning::$data['stump']['index'],
                                        "FULLTEXT(text)",
                                        "FULLTEXT(special_categories)",
                                        "FULLTEXT(comment)"
                                    ),
				"ENGINE=INNODB"
                        );
        }
    }
    
    //-------------Data Confirmation--------------------------------------
    class TableUserDataConfirmation extends SQLAppTable
    {
	public function __construct()
	{
            parent::__construct("user_data_confirmation",
		                array(  new SqlField(FieldsGeneralMeaning::$data['id']['name'],
			                             FieldsGeneralMeaning::$data['id']['type'],
			                             FieldsGeneralMeaning::$data['id']['options']
			                            ),
			                new SqlField('usrtype',
			                             'INT',
			                             array("NOT NULL")
			                            ),
			                new SqlField('refid',
			                             FieldsGeneralMeaning::$data['refid']['type'],
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
	                        array(// "FOREIGN KEY (refid) REFERENCES $ptableName(id) ON DELETE CASCADE " TODO integrate cascade delete on triger
                                      FieldsGeneralMeaning::$data['refid']['index'],
	                              FieldsGeneralMeaning::$data['stump']['index'],
                                      "UNIQUE KEY urdset(usrtype,refid,datatype)"
                                     ),
	                    	"ENGINE=INNODB"
                    );
	}
    }
	
    //-----------History---------------------------------
    class TableOperationsHistory extends SQLAppTable
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
                                        new SqlField('ip',
                                                     'VARCHAR(40)',
                                                     array("DEFAULT NULL")
                                                    ),
//                                        new SqlField('continent',  reserved for future use
//                                                     'VARCHAR(16)',
//                                                     array("DEFAULT NULL")
//                                                    ),
//                                        new SqlField('country',
//                                                     'VARCHAR(48)',
//                                                     array("DEFAULT NULL")
//                                                    ),
//                                        new SqlField('region',
//                                                     'VARCHAR(32)',
//                                                     array("DEFAULT NULL")
//                                                    ),
//                                        new SqlField('city',
//                                                     'VARCHAR(32)',
//                                                     array("DEFAULT NULL")
//                                                    ),
//                                        new SqlField('location_info',
//                                                     'TEXT',
//                                                     array("DEFAULT NULL")
//                                                    ),
                                        new SqlField('operation_id',
		                                     'INT',
		                                     array("NOT NULL")
		                                    ),
                                        new SqlField('operation_flags',
		                                     'INT',
		                                     array()
		                                    ),             
                                        new SqlField('operation',
		                                     'VARCHAR(128)',
		                                     array("NOT NULL")
		                                    ),
                                        new SqlField("args", 
                                                     "TEXT",
                                                     array()),
		                        new SqlField('time',
		                                     'DATETIME',
		                                     array("NOT NULL","DEFAULT NOW()")
		                                    ),
		                        new SqlField('retCode',
		                                     'INT',
		                                     array("NOT NULL")
		                                    ),
		                        new SqlField('result',
		                                     'TEXT',
		                                     array("NOT NULL")
		                                    ),             
		                        new SqlField(FieldsGeneralMeaning::$data['stump']['name'],
		                                     FieldsGeneralMeaning::$data['stump']['type'],
		                                     FieldsGeneralMeaning::$data['stump']['options']
		                                    )
		                    ),             
                             	array('INDEX refind(refid)',
                                      'INDEX opiding(operation_id)'
	                             ),
	                    	"ENGINE=INNODB"
                    );
        }
    }
	
    class TableAdmSessionsHistory extends SQLAppTable
    {
        CONST type = UserTypes::ADMIN;
		
	public function __construct()
	{
            parent::__construct("adm_sessions_history",
                                array(  new SqlField('id',
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
				array(  'INDEX idind(id)',
					FieldsGeneralMeaning::$data['refid']['index'],
					FieldsGeneralMeaning::$data['stump']['index'],
					'INDEX statind(status)'
                                    ),
				"ENGINE=INNODB"
			  );
	}
    }
	
    class TableUsrSessionsHistory extends SQLAppTable
    {
	CONST type = UserTypes::USER;
	
	public function __construct()
	{
            parent::__construct("usr_sessions_history",
                                array(  new SqlField('id',
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
				array(  'INDEX idind(id)',
                                        FieldsGeneralMeaning::$data['refid']['index'],
					FieldsGeneralMeaning::$data['stump']['index'],
					'INDEX statind(status)'
                                    ),
				"ENGINE=INNODB"
			    );
	}
    }	
        
?>