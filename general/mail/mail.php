<?php
require_once'LocalSettings.php';
require_once './global.php';
require_once './general/stringf/string_proc.php';

    class MailOutgoings
    {
        CONST SYSTEM = 'system';
	public static $outgoings = [ 
                                    MailOutgoings::SYSTEM => array( 'mail'=> 'noreply@parijana.org',
                                                                    'options'=>'',
                                                                    'mailer'=>'parijana.org'
					                          )
                                    ];
    }
	
    function mail_notification_send($to,$from,$subject,$msg,$headers = array())
    {
	$result = FALSE;
	if( array_key_exists($from, MailOutgoings::$outgoings) ){
            $fromMail = MailOutgoings::$outgoings[$from]['mail'];
            $options = MailOutgoings::$outgoings[$from]['options'];
            $fromMailer = MailOutgoings::$outgoings[$from]['mailer'];
            
            $headers[] = "From: $fromMail";
            $headers[] = "No-Reply:";
            $headers[] = "Mailer:$fromMailer";
            
            //TODO: this row is temporary for debaging
            $hdrs = implode(" ", $headers);
            makeLogNote("mail:[$to][$from][$subject][$result]$msg($hdrs)($options)\r\n",-1, FALSE,dirname( $_SERVER['SCRIPT_FILENAME'] )."/mails.dump");
            $result = TRUE;
            //$result = mail($to,$subject,$msg,implode("\r\n",$headers),$options);
	}
	return $result;
    }
    
    /*
$to =      'schelov@yandex.ru';
     $subject = 'mail test message';
    
     $var = "faejbfkjanl";
     $message = "<html>"
                ."<head>"
                ."<title>Mail test message</title>"
                ."<meta content=\"text/html\"; charset=\"UTF-8\" http-equiv=\"Content-Type\">"
                ."</head>"
                ."<body>"
                ."<p>Message sent successefully with var $var</p>"
                ."</body>"
                ."</html>";
    
    $headers[]   = 'MIME-Version: 1.0';
    $headers[]   = 'Content-type: text/html; charset=UTF-8';
    $headers[] = ''."To: Vladimir <$to>";
    $headers[] = 'From: noreply@parijana.org';
    
    if( mail($to,$subject,$message,implode("\r\n",$headers)) ){
        echo "Mail send attempt to $to was made successefully"."<br>";
    }
    else{
        echo "Mail send attempt to $to fail"."<br>";
    }
*/
    
?>