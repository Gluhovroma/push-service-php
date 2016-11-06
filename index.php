<?php 

header('Content-type: text/plain; charset=utf-8');
require_once("WindowsNotification.php");
class PushNotifications {
	// (Android)API access key from Google API's Console.
	private static $API_ACCESS_KEY = 'API_ACCESS_KEY';
	// (iOS) Private key's passphrase.
	private static $passphrase = 'passphrase';

	public function __construct() {
		exit('Init function is not allowed');
	}
	
        // Sends Push notification for Android users
	static public function android($data) {
	        $url = 'https://android.googleapis.com/gcm/send';
	        $message = array(
	            'title' => $data['mtitle'],
	            'message' => $data['mdesc'],
	            'subtitle' => '',
	            'tickerText' => '',
	            'msgcnt' => $data['mbadge'],
	            'vibrate' => 1,
	            'section' => $data['section']
	        );
	        
	        $headers = array(
	        	'Authorization: key=' .self::$API_ACCESS_KEY,
	        	'Content-Type: application/json'
	        );
	
	        $fields = array(
	            'registration_ids' => array($data['id']),
	            'data' => $message,
	        );

	        $ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result = curl_exec($ch );
			
			//echo $result;    	


				$res = (array)curl_getinfo($ch);
				$httpcode = $res["http_code"];
				//echo $httpcode;
			
			if ($httpcode == 200 && strpos($result,'InvalidRegistration')==false) {
				echo "СообщениеДоставлено";
			}
			else if ($httpcode == 200 && strpos($result,'InvalidRegistration')> 0) {
				echo "НеверныйКлюч";
			}
			else {
				echo "НеизвестнаяОшибка";
			}
			// здесь нужно зазобрать $result по состояниям если ошибка == InvalidRegistration возвращаем echo "НеверныйКлюч" и тд.
			curl_close($ch);
    	}
	
	// Sends Push's toast notification for Windows Phone 8 users
	static public function WP($data) {
			
			//$cha  = $id;

			$cha=$data['id'];
			$Notifier = new WindowsNotification\WindowsNotificationClass();     
			$Auth = $Notifier->AuthenticateService();
			//var_dump($Auth);
			$Options = new WindowsNotification\WNSNotificationOptions();   
			$Options->SetX_WNS_REQUESTFORSTATUS(WindowsNotification\X_WNS_RequestForStatus::Request);
			$Options->SetContentType(WindowsNotification\Content_Type::Standard);
			$Options->SetX_WNS_TYPE(WindowsNotification\X_WNS_Type::Toast);
			//echo $Auth->access_token;
			$MyAuthObject = new WindowsNotification\OAuthObject(array("token_type"=>$Auth->token_type, "access_token" => $Auth->access_token));
			$Options->SetAuthorization($MyAuthObject);
			$Notifier->SetOptions($Options);
			$res=$Notifier->Send($cha,WindowsNotification\TemplateToast::ToastText02($data['mtitle'], $data['mdesc'], $data['section'], WindowsNotification\TemplateToast::NotificationMail));
						
			$resStatus = $res["response"];
		
			if ($resStatus == 200) {
				echo "СообщениеДоставлено";
			}
			else if ($resStatus == 404) {
				echo "НеверныйКлюч";
			}
			else {
				echo "НеизвестнаяОшибка";
			}
	}
	
    
	static public function iOS($data) {

		function checkAppleErrorResponse($fp) {
			
		   //byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
		   $apple_error_response = fread($fp, 6);
		   //NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script and wait forever when there is no response to be sent.

		   if ($apple_error_response) {
		        //unpack the error response (first byte 'command" should always be 8)
		        $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);

		        if ($error_response['status_code'] == '0') {
		            $error_response['status_code'] = 'СообщениеДоставлено';
		        } else if ($error_response['status_code'] == '1') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '2') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '3') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '4') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '5') {
		            $error_response['status_code'] = 'НеверныйКлюч';
		        } else if ($error_response['status_code'] == '6') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '7') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else if ($error_response['status_code'] == '8') {
		            $error_response['status_code'] = 'НеверныйКлюч';
		        } else if ($error_response['status_code'] == '255') {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        } else {
		            $error_response['status_code'] = 'НеизвестнаяОшибка';
		        }
		        return $error_response['status_code'];
		   }
		   return "СообщениеДоставлено";
		}

		$deviceToken = $data['id'];
		$ctx = stream_context_create();
		// ck.pem is your certificate file
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'apnssert.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', self::$passphrase);

		// Open a connection to the APNS server
		$fp = stream_socket_client(
			'ssl://gateway.sandbox.push.apple.com:2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		stream_set_blocking ($fp, 0);
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		// Create the payload body
		$body['aps'] = array(
			'alert' => array(
			    'title' => $data['mtitle'],
                'body' => $data['mdesc'],                
			 ),
			'badge' =>  (int) $data['mbadge'],
			'section' => 'РазделПитание'
		);
		
		$payload = json_encode($body); 
 	
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;		
		
		// Send it to the server
		fwrite($fp, $msg);
	
		$result = checkAppleErrorResponse($fp);
		usleep(500000);
		$result  = checkAppleErrorResponse($fp);
    	echo $result;
		fclose($fp);		
	}    
}

$json = json_decode(file_get_contents("php://input"), true);

if ($_GET['platform'] == 'APNS'){
	return PushNotifications::iOS($json);
} elseif ($_GET['platform'] == 'WNS') {
	return PushNotifications::wp($json);
} elseif ($_GET['platform'] == 'GCM') {
	return PushNotifications::android($json);
} else {
	echo "VSIO OCHEN' PLOHO PAREN'";
}
?>