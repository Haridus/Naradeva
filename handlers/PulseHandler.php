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
require_once ('UserRequestsHandler.php');

//--------------------------------------------------------------------------------
    class PulseHandler extends AutorazedOperationHandler
    {
        public function __construct($operation, $session)
        {
            parent::__construct($operation,$session,Operations::PULSE);
        }
		
        public function handleBodyEx($dblink, $session, $user) : Response
        {
            $result = NULL;

            //meanwhile now requests does not connected to users id then no need to clean requests data here
            
            $id = $user->id;
            $this->operation->getArg(0,$sh);
            $this->operation->getArg(1,$dl);
            $this->operation->getArg(2,$signal);
                
            $regionHandlers = calculateRegionHandlers_0($sh, $dl);
            $region = regionFromHandlers_0($regionHandlers);            
                
            $locationTable = new TableUsersLocation();
                        
            $kv = array('sh' => $sh,
                        'dl' => $dl,
                        'region_section_0' => $regionHandlers[0],
                        'region_section_1' => $regionHandlers[1],
                        'region_section_2' => $regionHandlers[2],
                        'region_section_3' => $regionHandlers[3],
                        'region_section_4' => $regionHandlers[4],
                        'region' => $region,
                        'state' => $signal
                       );
            
            $dblink->beginTransaction();
            
            $query = $locationTable->update("WHERE (refid = $id)", array_keys($kv), array_values($kv));
            $stmt = prepare_and_execute($dblink, $query, array());
            if( !$stmt ){
                throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to update last position","",__FILE__.__LINE__);
            }
            $stmt = NULL;
            
            if( $user->data["role"] == UserRoles::HELPER ){
                $locationHistoryTable = new TableUserLocationHistory($id);
                $query = $locationHistoryTable->create("IF NOT EXISTS ");
                $stmt = prepare_and_execute($dblink, $query);
                if(!$stmt){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to save last position(1)","",__FILE__.__LINE__);
                }
                $stmt = NULL;
                
                $query = $locationHistoryTable->insert(array_keys($kv), array_values($kv));
                $stmt = prepare_and_execute($dblink, $query);
                if(!$stmt){
                    throw new ProcessingExeption(ErrorCodes::OPERATION_UNKNOWN_ERROR,"fail to save last position(2)","",__FILE__.__LINE__);
                }
                $stmt = NULL;
            }
            
            $dblink->commit();
            
            $result  = new Response($this->operation->operation,ErrorCodes::OK,0);
            return $result;
        }	
    }

?>