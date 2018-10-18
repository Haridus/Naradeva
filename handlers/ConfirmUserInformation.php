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
require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once('./general/stringf/generate_password.php');
require_once('./general/query/query.php');
require_once('./general/mail/mail.php');
require_once('OperationsHandlersBase.php');


    class ConfirmUserDataHandler extends OperationsHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::CONFIRM_USER_DATA);
        }
		
        public function handleBody($dblink,$session) : Response
        {
            $this->operation->getArg(0,$stump);
            $stumpData = decode_obj_stump($stump);
            
            $utype = $stumpData['usrtype'];
            $refid = $stumpData['refid'];
            $datatype = $stumpData['datatype'];
                        
            $whereClause = "WHERE (refid = $refid) and (usrtype = $utype) and (datatype = $datatype) LIMIT 1";
            $userDataConfirmationTable = new TableUserDataConfirmation();
            $query = $userDataConfirmationTable->select($whereClause);
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to confirm user data(1)","",__FILE__.__LINE__);
            }            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = NULL;
            
            if( !$row ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_LINK_EXPIRED,"link expired or does not exists","",__FILE__.__LINE__);
            }
            
            $kv = array();
            switch ($datatype){
                case UserDataTypes::MAIL:
                    $kv['mail_confirmed'] = 1;
                    break;
                case UserDataTypes::PHONE:
                    $kv['phone_confirmed'] = 1;
                    break;
                default :
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"bad user data type","",__FILE__.__LINE__);
                    break;
            }

            $mtable = NULL;
            switch ($utype) {
                case UserTypes::ADMIN_TYPE_ID:
                    $mtable = new TableAdmins();
                    break;
                case UserTypes::USER_TYPE_ID:
                    $mtable = new TableUsers();
                    break;
                default:
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"bad user type","",__FILE__.__LINE__);
                    break;
            }
            
            $dblink->beginTransaction();
            
            $query = $mtable->update("WHERE (id = $refid) LIMIT 1", array_keys($kv), array_values($kv));
            $stmt = prepare_and_execute( $dblink, $query, array() );
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to confirm user data(2)","",__FILE__.__LINE__);
            }
            $stmt = NULL;
            
            $query = $userDataConfirmationTable->delete($whereClause);
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                //TODO: nothing to do will be removed later during clean up
            }
            $stmt = NULL;
            
            $dblink->commit();
            
            $result = new Response($this->operation->operation,ErrorCodes::OK,"");
            return $result;			
        }
    }


?>
