<?php

    require_once './handlers/LoginLogoutHandler.php';
    require_once './handlers/CreateDeleteUserHandler.php';
    require_once './handlers/GetUserInfoHandler.php';
    require_once './handlers/ChangeUserInfoHandler.php';
    require_once './handlers/UserRequestsHandler.php';
    require_once './handlers/HelpersRequestsHandler.php';
    require_once './handlers/PulseHandler.php';
    require_once './handlers/ConfirmUserInformation.php';
    
    class OperationsHandlersFactory
    {
        static public $handlers = [
                                    Operations::LOGIN           => "LoginHandler",
                                    Operations::LOGIN_LMP       => "LMPLoginHandler",
                       
                                    Operations::LOGOUT          => "LogoutHandler",
                                    Operations::REGISTRATE      => "RegistrateUserHandler",
                        //            Operations::CREATE_USER     => "CreateUserHandler",
                        //            Operations::DELETE_USER     => "DeleteUserHanler",
                        //            Operations::CONFIRM_USER_DATA => "ConfirmUserDataHandler",
                
                                    Operations::GET_USER => "GetUserHandler",
                                    Operations::GET_USER_INFO => "GetUserInfoHandler",
                                            
                        //            Operations::GET_USERS_COUNT => "GetUsersCountHandler",
                      	//	      Operations::GET_USERS_LIST  => "GetUsersListHandler",
                                       
                                    Operations::CHANGE_PASSWORD => "ChangePasswordHandler",
                        //            Operations::CHANGE_CONTACT_DATA => "ChangeContactDataHandler",
                                    Operations::CHANGE_PROFILE_DATA => "ChangeProfileDataHandler",
                        //            Operations::CHANGE_RIGHTS => "ChangeRightsHandler",  
            
                                    Operations::RESTORE_PASSWORD => "RestorePasswordHandler",
                                    
                                    Operations::ADD_REQUEST => "AddRequestHandler",
                        //            Operations::CHANGE_REQUEST => "ChangeRequestHandler",
                                    Operations::GET_REQUEST_INFO => "GetRequestInfoHandler",
                        //            Operations::GET_CURRENT_REQUEST => "GetCurrentRequestHandler",
                                    Operations::CLOSE_REQUEST => "CloseRequestHandler",
					   
                                    Operations::GET_REQUESTS_LIST => "GetRequestsList",
                                    Operations::TAKE_UP_CALL => "TakeUpCallHandler",
                                    Operations::GIVE_UP_CALL => "GiveUpCallHandler",
                                                                                                
                                    Operations::PULSE => "PulseHandler",
            
                                    Operations::GET_HELPERS_CANDIDATES_COUNT => "GetHelpersCandidatesCount",
                                    Operations::GET_HELPERS_CANDIDATES_LIST  => "GetHelpersCandidatesList",
                                   // Operations::GET_HELPERS_IN_REGION        => "",  
                                    Operations::SET_HELPERS_DATA_CHECKED     => "SetHelpersDataCheckedHandler",
                                    Operations::ADMITT_HELPER                => "AdmittHelperHandler",
                                    Operations::SET_HELPER_DATA_CHECKED_AND_ADMITT => "SetHelperDataCheckedAndAdmittHandler" 
                        ];
		
    }

?>