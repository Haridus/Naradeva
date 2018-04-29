<?php
	require_once('LocalSettings.php');
	
	class MailOutgoings
	{
		CONST SYSTEM = 'system';
		public static $outgoings = [ 
									  MailOutgoings::SYSTEM => array( 'mail'=> '',
									                                  'options'=>'',
									                                  'mailer'=>''
									                                 )
								   ];
	}
	
	function sendMailNotification($to,$from,$subject,$msg)
	{
		$result = FALSE;
		if( in_array($from,MailOutgoings::$outgoings) ){
			$fromMail = MailOutgoings::$outgoings[$from]['mail'];
			$fromMailer = MailOutgoings::$outgoings[$from]['mailer'];
			$headers = "From:$fromMail"."\r\n".
			           "No-Reply:"."\r\n".
			           "Mailer:$fromMailer"."\r\n";
			$options = MailOutgoings::$outgoings[$from]['options'];          
			mail($to,$subject,$msg,$headers,$options);
		}
		return $result;
	}
?>