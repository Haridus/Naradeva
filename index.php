<?php
require_once('./install.php');
require_once('./global.php');
require_once('./processingExeption.php');

//http://192.168.77.4/civilhelper?appkey=111222999&operation=login(admin,admin,admin)&session=ect

ignore_user_abort(TRUE);

require_once('request.php');
require_once('response.php');
require_once('operation.php');
require_once('operationHandler.php');
require_once('error.php');

$responce = NULL;
try{
    $request = new Request();
    if( !$request->isValid ){
        throw new ProcessingExeption(ErrorCodes::INVALID_REQUEST,"invalid request: ".$request->operation->operationString,$request->operation->operation,"\\");
    }
    
    if( AppSettings::installedState == 0 ){
        switch ( install() ) {
            case TRUE:
                debug('SUCCESEFULLY INSTALLED',DebugLevels::INFO_MESSAGES);
                $fcontent = file_get_contents('./LocalSettings.php');
                $fcontent = preg_replace('/\binstalledState\b\s*=\s*\d{1,1}/', 'installedState = 1', $fcontent);
                file_put_contents('./LocalSettings.php', $fcontent);
                break;
            default:
                debug('FAIL TO INSTALL', DebugLevels::URGENT_AND_SYSTEM_MSG);
                throw new ProcessingExeption(1000,"FAIL TO INSTALL APP");
                break;
        }
    }
       
    $operation = $request->operation;
    $session   = $request->session;
    $handler = NULL;

    if( array_key_exists($operation->operation, OperationsHandlersFactory::$handlers) ){
        $handler = new OperationsHandlersFactory::$handlers[$operation->operation]($operation,$session);
    }
    if( !$handler ){
        throw new ProcessingExeption(ErrorCodes::OPERATION_UNSUPPORTED,"operation not supported",$request->operation->operation,"\\"); 
    }

    $responce = $handler->handle();
}
catch(ProcessingExeption $e){
    debug($e,0);
    $retCode  = $e->retCode;
    $retValue = $e->msg;//." see ".$e->method;
    $opName   = $e->request;
    $responce = new Response($opName, $retCode, $retValue);
}	
finally{
}	

ignore_user_abort(FALSE);

echo $responce->response();	


//==============================================================================
//require_once './general/datetime/datetime.php';
//
//$date = date_create("1955-01-01");
//if(date_is_valid($date) ){
//    echo 'valid'."<br>";
//    
//    $datecat = date_to_category($date);
//    echo $datecat."<br>";
//    
//    if( date_is_in_category($date, 2) ){
//        echo 'in category'."<br>";
//    }
//    else{
//        echo 'not in category'."<br>";
//    }
//}
//else{
//    echo 'invalid'."<br>";
//}
////$now = time();
//echo $now."<br>";
//echo date('Y-m-d H:i:s', $now)."<br>";
//$now += 60*60;
//echo date('Y-m-d H:i:s', $now)."<br>";
//require_once './general/encrypt_decrypt/encrypt_decrypt_string.php';
//
//$stumpKey = "makeAteam";
//$passKey  = "HareKrishna";
//$registerKey = "RadhaKrishna";
//
//$pass = "RadheShiam";
//
//echo Encrypt($pass, $stumpKey)."<br>";
//echo Encrypt($pass, $passKey)."<br>";
//echo Encrypt($pass, $registerKey)."<br>";
//require_once './operation.php';
//echo implode("<br>", Operations::getOperationsSemantics());
//
//require_once './operation.php';
//foreach (Operations::getOperationsSemantics() as $value) {
//    echo $value."<br>";
//} 
//
//require_once './user.php';
//require_once './AppTables.php';
//$tables = array(new TableAdmins(),
//	        new TableAdminsProfiles(),
//                new TableAdmSessions(),
//                new TableAdmSessionsHistory(),
//	                
//                new TableUsers(),
//	        new TableUsersProfiles(),
//	        new TableUsrSessions(),
//                new TableUsrSessionsHistory(),
//                new TableUsersLocation(),
//		new TableUserDataConfirmation(),
//	                
//                new TableUsersRequest(),
//                new TableUsersRequestHistory(),
//			
//                new TableOperationsHistory()                         
//                );
//foreach ($tables as $table){
//    echo "<p>TABLE ".$table->name."</p>";
//    foreach ($table->fields as $field){
//        echo "<p>FIELD ".$field->name." ".$field->type." ".implode(" ", $field->options)."</p>";
//    }
//}
//require_once './general/region/region.php';
//
//$sh = 57.30;
//$dl = 36.30;
//
//$parts = calculateNearbyRegionsHandlers_0($sh, $dl);
//$parts[] = calculateRegionHandlers_0($sh, $dl);
//
////echo var_dump($parts);
//
//foreach ($parts as $part){
//    foreach ($part as $value){
//        echo "$value ";
//    }
//    echo "<br>";
//}

//
//require_once './user.php';
//require_once './PDO/connect.php';
//
//$adm = new Admin(getDefaultConnect());
//$data = $adm->getCoreData('admin');
//echo var_dump($data);
//
//$data = $adm->get();
//echo var_dump($data);
//require_once './obj.php';
//require_once './PDO/connect.php';
//require_once './UserTables.php';
//
//$obj = new SQLObj(getDefaultConnect(),array(new TableAdminsExUsr,new TableAdminsProfilesExUsr));
//
//$kv = array( 'login' => "'adm2'",
//             'pass' =>  "'123'",
//             'mail'   => "'1@mai.ru'",
//             'mail_confirmed' => 0,
//             'mail_confirmed' => 0,
//             'phone' => "'123'",
//             'phone_confirmed' => 0,
//             'role' => 0,
//             'rights' => 0,
//             'stump' => "'21312341343'",
//             'refid' => 0
//            );
//                           
//$obj->insert(array_keys($kv), array_values($kv));

//require_once ('./PDO/connect.php');
//require_once './AppTables.php';
//require_once './operationHandler.php';
//
//$dblink = getDefaultConnect();
//
//$tt = new TableUsersRequest();
//$kv = array('refid' => 10,
//            'ctime' => "ADDTIME(NOW(),'0:30:0')",
//            'sh'=>12.41,
//            'dl'=>213.41,
//            'region'=>1,
//            'cats'=>1,
//            'specats'=>"'1,2,3'",
//            'helpers_req'=>1,
//            'helpers_signed'=>1,
//            'helpers'=>"'1       1'",
//            'status' => 1,
//            'reward'=>100,
//            'text'=>"'Hello World Text etc.'",
//            'stump'=>"'dfqewkdnf dajnfb abdfkb dhkasbf kabdfkhb kabf kbadhfkb dhkab f'");
//
//$query = $tt->insert(array_keys($kv), array_values($kv));
//$stmt = prepare_and_execute($dblink, $query, array());
//$stmt = NULL;
//
//
//require_once './general/validate/validate.php';
//require_once './general/validate/phones.php';
//
//$logins = array('a1231edde','28unfcqwhf2o3n','d792yw DA ADADA','$%38ET86*708)*&0E8170870','ADMIN','user','parijana');
//foreach ($logins as $login) {
//    if( validate_login($login) ){
//        echo 'CORRENT LOGIN :'.$login."<br>";
//    }
//    else{
//        echo 'INCORRENT LOGIN :'.$login."<br>";
//    }
//}
//
//$mails = array('1@mail.ru','dadno h13heq qd','a213413e','janfwnf&%%&#@mail.ru','ok@mail.ru');
//foreach ($mails as $mail) {
//    if(validate_mail($mail) ){
//        echo 'CORRENT MAIL :'.$mail."<br>";
//    }
//    else{
//        echo 'INCORRENT MAIL :'.$mail."<br>";
//    }
//}
//
//$phones = array('+74953761660','376-16-60','3761660','edfm 2ip8hjr 8021e','93841830741741807380173','213123','+1679ey179y');
//foreach ($phones as $phone) {
//    if(validate_phone($phone) ){
//        echo 'CORRENT PHONE :'.$phone."<br>";
//    }
//    else{
//        echo 'INCORRENT PHONE :'.$phone."<br>";
//    } 
//    $cphone = PhoneConverter::convert($phone);
//    echo $cphone."<br>";
//}
?>