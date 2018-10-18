<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once './user.php';
require_once './general/mail/mail.php';
require_once './general/stump/stump.php';
require_once './general/stringf/string_proc.php';
require_once './general/query/query.php';
require_once './LocalSettings.php';
    
    function buildOperationRequestString($operation,$session = NULL)
    {
        $baseUrl = AppSettings::baseUrl;
        $appkey  = AppSettings::appkey;
        
        $clauseEntryes = [];
        $clauseEntryes[] = "operation=$operation";
        if( $session ){
            $clauseEntryes[] = "session=$session";
        }
        $clause = implode("&", $clauseEntryes);
        $result  = "$baseUrl/?appkey=$appkey&$clause";
        return $result;
    }

    function notification_message_make_on_contact_data_change($locale, $link,&$subject,&$headers)
    {
        $result = NULL;
        switch ($locale)
        {
            case 'ru':
            default:
                $appName = AppSettings::appName;
                $subject = "Подтверждение данных";
                $msg = "<html>"
                      ."    <head>"
                      ."        <title>Mail test message</title>"
                      ."        <meta content=\"text/html\"; charset=\"UTF-8\" http-equiv=\"Content-Type\">"
                      ."    </head>"
                      ."    <body>"
                      ."        <p>Вы зарегистрировались в приложении $appName. Для того, чтобы подтвердить адрес электронной почты перейдите по <a href=\"$link\">ссылке</a></p>"
                      ."    </body>"
                      ."</html>";
                $result = $msg;
                $headers = array();
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/text; charset=UTF-8';
                break;
        }
        return $result;
    }
    
    function notification_mail_send_on_contact_data_change($dblink, $usertypeid, $datatype, $refid, $mail, $locale = AppSettings::defaultLocale)
    {        
        $result = FALSE;
                       
        $kv = array('usrtype' => $usertypeid,
                    'refid'    => $refid,
                    'datatype' => $datatype
                   );
        $stump = make_obj_stump($kv);
        $kv['stump'] = sql_field_string($stump);
        
        $dataConfirmationTable = new TableUserDataConfirmation();        
        $query = $dataConfirmationTable->insert(array_keys($kv), array_values($kv));
        $stmt = prepare_and_execute($dblink, $query, array());
        if( $stmt ){
            $link = buildOperationRequestString("confirm_user_data($stump)");
            $msg = notification_message_make_on_contact_data_change($locale, $link,$subject,$headers);
            $result = mail_notification_send($mail,MailOutgoings::SYSTEM,$subject,$msg,$headers);
        }     
        return $result;
    }

