<?php
/*
require_once('operationHandler.php');
$data = calculateNearbyRegionsHandlers(57.0,37.0);
echo var_dump($data)."<br>";
$data = calculateNearbyRegionsHandlers(179.9955,89.9955);
echo var_dump($data)."<br>";

echo var_dump( calculateRegionHandlers(57.015,37.015) )."<br>";
*/
/*
CONST TEST = 'test';
$testAr = [ TEST => function($args){return count($args);} ];
$args =['1','2','3'];
echo $testAr[TEST]($args);
*/
/*
require_once('AppTables.php');
echo var_dump( FieldsGeneralMeaning::$data);
echo FieldsGeneralMeaning::$data['login']['name']."<br>";
echo FieldsGeneralMeaning::$data['login']['type']."<br>";
echo var_dump( FieldsGeneralMeaning::$data['login']['options']);
*/
/*
require_once('general/encrypt_decrypt/encrypt_decrypt_string.php');
require_once('LocalSettings.php');

$data = Encrypt(AppSettings::passKey,"HareKrishna");
$str  = Decrypt(AppSettings::passKey,$data);
echo $data."<br>";
echo $str."<br>";
*/
/*
	require_once('install.php');
	install();
//  uninstall();
*/
	require_once('LocalSettings.php');
	require_once('operation.php');
	require_once('operationHandler.php');
	require_once('init.php');
	require_once('request.php');
	require_once('response.php');
	require_once('processingExeption.php');
	require_once('error.php');
	
//http://192.168.77.4/naradeva/naradeva/index.php?appkey=12345678&operation=login(admin,admin)
	$responce = new Response("",0,"");
	try{
		$request = new Request();
		$responce = new Response($request->operation->operation,0,"");
		if( $request->isValid ){
			if( initialize() ){
				$operation = $request->operation;
				$session   = $request->session;
				$handler = NULL;
				
				if( array_key_exists($operation->operation, OperationsHandlersFactory::$handlers) ){
					$handler = new OperationsHandlersFactory::$handlers[$operation->operation]($operation,$session);
				}
								
				if( $handler ){
					$responce = $handler->handle();
				}
				else{
					throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"operation not supported",$request->operation->operation,"\\");
				}
				
				deinitialize();
			}
			else{
				throw new ProcessingExeption(ErrorCodes::INITIALIZATION_FAIL,"internal error: initialization fail",$request->operation->operation,"\\");
			}	
		}
		else{
			throw new ProcessingExeption(ErrorCodes::INVALID_REQUEST,"invalid request: ".$request->operation->operationString,$request->operation->operation,"\\");
		}
	}
	catch(ProcessingExeption $e){
		if( $responce->operation == NULL or ( strlen($responce->operation) == 0 )  ){	$responce->operation = $e->request;	}
		$responce->retCode = $e->retCode;
		$responce->retValue = $e->msg." see ".$e->method;
	}	
	finally
	{
	
	}	
	
	echo $responce->response();		
?>