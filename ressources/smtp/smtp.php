<?php
/**
* Filename.......: class.smtp.inc
* Project........: SMTP Class
* Version........: 1.0.5
* Last Modified..: 21 December 2001
*/

	define('SMTP_STATUS_NOT_CONNECTED', 1, TRUE);
	define('SMTP_STATUS_CONNECTED', 2, TRUE);

	class smtp{

		var $authenticated;
		var $connection;
		var $recipients;
		var $headers;
		var $timeout;
		var $errors;
		var $status;
		var $body;
		var $from;
		var $host;
		var $port;
		var $helo;
		var $auth;
		var $user;
		var $pass;
		var $bindto;
		var $DonotResolvMX=false;
		var $ERROR_SOCK="";
		var $ERROR_SOCK_ARRAY=array();
		var $mxs=array();
		var $error_number;
		var $error_text;
		var $debug=false;

		/**
        * Constructor function. Arguments:
		* $params - An assoc array of parameters:
		*
		*   host    - The hostname of the smtp server		Default: localhost
		*   port    - The port the smtp server runs on		Default: 25
		*   helo    - What to send as the HELO command		Default: localhost
		*             (typically the hostname of the
		*             machine this script runs on)
		*   auth    - Whether to use basic authentication	Default: FALSE
		*   user    - Username for authentication			Default: <blank>
		*   pass    - Password for authentication			Default: <blank>
		*   timeout - The timeout in seconds for the call	Default: 5
		*             to fsockopen()
        */

		function smtp($params = array()){
			if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
			if(!defined('CRLF'))
				define('CRLF', "\r\n", TRUE);

			$this->authenticated	= FALSE;			
			$this->timeout			= 5;
			$this->status			= SMTP_STATUS_NOT_CONNECTED;
			$this->host				= 'localhost';
			$this->port				= 25;
			$this->helo				= 'localhost';
			$this->auth				= FALSE;
			$this->user				= '';
			$this->pass				= '';
			$this->errors   		= array();

			foreach($params as $key => $value){
				$this->$key = $value;
			}
			if($this->auth){
				if($this->debug){$this->events("DEBUG:: AUTH ENABLED....", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);}
			}	
			
			if(!$this->DonotResolvMX){
				if(!is_array($this->recipients)){
					if(strpos($this->recipients, "@")>0){
						$this->host=$this->resolveMX($this->recipients);
					}
				}
			}
			
			
			
		}
		
		
		function resolveMXTable($recipient){
			$tb=explode("@", $recipient);
			$mxs=array();
			getmxrr($tb[1], $mxhosts,$mxWeight);
			while (list ($index, $WEIGHT) = each ($mxWeight) ){
				$mxs[]=array($WEIGHT,$mxhosts[$index]);
				
			}
			
			ksort($mxs);
			return $mxs;
			
		}
		
		
		function resolveMX($recipient){
			unset($this->mxs);
			$tb=explode("@", $recipient);
			getmxrr($tb[1], $mxhosts,$mxWeight);
			while (list ($index, $WEIGHT) = each ($mxWeight) ){
				if(isset($mxs[$WEIGHT])){$WEIGHT++;$mxs[$WEIGHT]=$mxhosts[$index];}
				
			}
			
			ksort($mxs);
			while (list ($WEIGHT, $mx) = each ($mxs) ){$this->mxs[]=$mx;}
			$this->events("Prefered MX for {$tb[1]} = `{$this->mxs[0]}`", __FUNCTION__, __FILE__,__LINE__);
			return $this->mxs[0];
		}
		
		function ResolveMXDomain($domain){
			unset($this->mxs);
			$mxs=null;
			getmxrr($domain, $mxhosts,$mxWeight);
			
	
			
			if(!is_array($mxWeight)){
				if($GLOBALS["VERBOSE"]){echo "$domain = mxWeight not an array function:".__FUNCTION__." Line:".__LINE__."\n";}
				if(!is_array($mxhosts)){
					if($GLOBALS["VERBOSE"]){echo "$domain = mxhosts not an array function:".__FUNCTION__." Line:".__LINE__."\n";}
					return null;
				}
				if($GLOBALS["VERBOSE"]){echo "$domain = {$mxhosts[0]} function:".__FUNCTION__." Line:".__LINE__."\n";}
				$this->mxs[0]=$mxhosts[0];
				return $this->mxs[0];
			}
			while (list ($index, $WEIGHT) = each ($mxWeight) ){
				if($GLOBALS["VERBOSE"]){echo "$domain = WEIGHT:$WEIGHT for {$mxhosts[$index]} function:".__FUNCTION__." Line:".__LINE__."\n";}
				//$this->events("MX WEIGHT:$WEIGHT for {$mxhosts[$index]}", __FUNCTION__, __FILE__,__LINE__);
				if(isset($this->mxs[$WEIGHT])){$WEIGHT++;$mxs[$WEIGHT]=$mxhosts[$index];continue;}
				$mxs[$WEIGHT]=$mxhosts[$index];
				
			}
			
			if(is_array($mxs)){
				ksort($mxs);
				while (list ($WEIGHT, $mx) = each ($mxs) ){$this->mxs[]=$mx;}
			}else{
				if($GLOBALS["VERBOSE"]){echo "$domain = mxs not an array function:".__FUNCTION__." Line:".__LINE__."\n";}
			}
			
			
			//$this->events("Prefered MX for `{$domain}` = `{$this->mxs[0]}`", __FUNCTION__, __FILE__,__LINE__);
			if(isset($this->mxs)){
				return $this->mxs[0];
			}
		}		
		
		public static function events($text,$function,$file,$line){
			$file=basename($file);
			if($GLOBALS["VERBOSE"]){echo "$file::$function $text in line $line\n";}
			if(!$GLOBALS["AS_ROOT"]){return;}
			if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
			$logFile="/var/log/artica-smtp.log";
			if(isset($GLOBALS["WRITETOFILE"])){$logFile=$GLOBALS["WRITETOFILE"];}
			if(!is_dir(dirname($logFile))){
				echo dirname($logFile)." no such dir....\n";
				return;}
		   	if (is_file($logFile)) {$size=filesize($logFile);if($size>9000000){unlink($logFile);}}
		   	$date=date('m-d H:i:s');
			$logFile=str_replace("//","/",$logFile);
			$f = @fopen($logFile, 'a');
			$file=basename($file);
			$final="$date $file:[{$GLOBALS["MYPID"]}]:[$function::$line] $text\n";
			if($GLOBALS["VERBOSE"]){echo $final;}
			@fwrite($f,$final );
			@fclose($f);

			if(isset($GLOBALS["LOGPERDOMAIN"])){
				$logFile="/var/log/artica-{$GLOBALS["LOGPERDOMAIN"]}.log";
				if(!is_dir(dirname($logFile))){return;}
			   	if (is_file($logFile)) {$size=filesize($logFile);if($size>9000000){unlink($logFile);}}
			   	$date=date('m-d H:i:s');
				$logFile=str_replace("//","/",$logFile);
				$f = @fopen($logFile, 'a');
				$file=basename($file);
				$final="$date $file:[{$GLOBALS["MYPID"]}]:[$function::$line] $text\n";
				@fwrite($f,$final );
				@fclose($f);				
			}
			
			
		}
		

		/**
        * Connect function. This will, when called
		* statically, create a new smtp object, 
		* call the connect function (ie this function)
		* and return it. When not called statically,
		* it will connect to the server and send
		* the HELO command.
        */
		
		
		function connect_stream(){
			if($this->bindto==null){return false;}
			if (!function_exists('stream_context_create')){return false;}
			if (!function_exists('stream_socket_client')){return false;}
			$socket_options = array('socket' => array('bindto' => "$this->bindto:0"));
			$socket_context =@stream_context_create($socket_options);
			if(!is_numeric($this->timeout)){$this->timeout=5;}
			$this->connection =@stream_socket_client("tcp://$this->host:$this->port", $errno,$errstr, $this->timeout, STREAM_CLIENT_CONNECT, $socket_context);
			if(!$this->connection){
				//$this->events("Failed to stream_context_create from [$this->bindto]:0 to [$this->host:$this->port] Err.$errno $errstr", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				return false;
			}
			return true;
		}
		

		function connect($params = array()){

			if(count($params)>0){foreach($params as $key => $value){$this->$key = $value;}}
			
			
			if(!isset($this->status)){
				$obj = new smtp($params);
				if($obj->connect()){
					$obj->status = SMTP_STATUS_CONNECTED;
				}

				return $obj;

			}else{
				$errorplus=null;
				   if(!$this->connect_stream()){
				   		$this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
				   		if(!$this->connection){
				   			$this->errors[]="Failed to fsockopen to [$this->host]:($this->port) Err.$errno $errstr";
				   			$this->events("Failed to fsockopen to [$this->host]:($this->port) Err.$errno $errstr", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				   			return false;
				   		}
				   		//$this->events("Success to fsockopen to $this->host", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				   		if(function_exists('socket_set_timeout')){@socket_set_timeout($this->connection, 5, 0);}
				   	
				   }
				  
				
				if(!$this->connection){
					$this->errors[]="Failed to connect {$errorplus}to $this->host:$this->port Err.$errno $errstr";
					$this->events("Failed to connect {$errorplus}to $this->host:$this->port Err.$errno $errstr", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				}
				
				if($this->connection){$this->status=SMTP_STATUS_CONNECTED;}

				$greeting = $this->get_data();
				if($this->debug){$this->events("DEBUG:: greeting: `$greeting`", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);}
				
				$this->PARSE_ERROR($greeting);
				if($this->DETECTED_ERROR($greeting)){
					$this->errors[]="ERROR DETECTED IN GREETING...\"".trim($greeting);
					if($this->debug){$this->events("ERROR DETECTED IN GREETING...\"".trim($greeting)."\"", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);}
					return false;
				}
				
				if(is_resource($this->connection)){
					if($this->auth){return $this->ehlo();}
					 //$this->events("Send HELO command...", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
					 return $this->helo();
				}else{
					$this->errors[] = "Failed to connect to $this->host:$this->port Err.$errno: ".$errstr;
					return FALSE;
				}
			}
		}

		/**
        * Function which handles sending the mail.
		* Arguments:
		* $params	- Optional assoc array of parameters.
		*            Can contain:
		*              recipients - Indexed array of recipients
		*              from       - The from address. (used in MAIL FROM:),
		*                           this will be the return path
		*              headers    - Indexed array of headers, one header per array entry
		*              body       - The body of the email
		*            It can also contain any of the parameters from the connect()
		*            function
        */

		function send($params = array()){

			foreach($params as $key => $value){$this->set($key, $value);}

			if($this->is_connected()){

				// Do we auth or not? Note the distinction between the auth variable and auth() function
				if($this->auth AND !$this->authenticated){
					if(!$this->auth())
						return FALSE;
				}

				if(!$this->mail($this->from)){
					$this->events("Failed while sending MAIL FROM <$this->from> $this->error_number `$this->error_text`", __FUNCTION__, __FILE__, __LINE__);
					return FALSE;	
				}
				
				
				if(is_array($this->recipients)){
					foreach($this->recipients as $value){
						$this->rcpt($value);
					}
					
				}else{
					if(!$this->rcpt($this->recipients)){
						$this->events("Failed while sending RCPT <$this->recipients> $this->error_number `$this->error_text`", __FUNCTION__, __FILE__, __LINE__);
						return FALSE;
					}
					
				}

				if(!$this->data()){
					$this->events("Failed while sending data", __FUNCTION__, __FILE__, __LINE__);
					return FALSE;
					
				}

				// Transparency
				if(strlen($this->headers)>5){
					$headers = str_replace(CRLF.'.', CRLF.'..', trim(implode(CRLF, $this->headers)));
					$body    = str_replace(CRLF.'.', CRLF.'..', $this->body);
					$body    = $body[0] == '.' ? '.'.$body : $body;
					$this->send_data($headers);
					$this->send_data('');
				}else{
					$body=$this->body;
				}
				$this->send_data($body);
				$this->send_data('.');
				$result_end_data=trim($this->get_data());
				$this->PARSE_ERROR($result_end_data);
				$result_end_data_error=substr($result_end_data, 0, 3);
				
				if($this->DETECTED_ERROR($result_end_data_error)){
					$this->events("TRANSACTION FAILED: [$result_end_data_error] `$result_end_data`", __FUNCTION__, __FILE__, __LINE__);
					return false;
				}
				return true;
				
			}else{
				$this->errors[] = 'Not connected!';
				return FALSE;
			}
		}
		
		/**
        * Function to implement HELO cmd
        */
		
		function PARSE_ERROR($content){
			if(strlen(trim($content))==0){return;}
			if(preg_match("#^([0-9\.]+)\s+(.+)#", trim($content),$re)){
				$this->error_number=$re[1];
				$this->error_text=$re[2];
			}
			
		}
		
		function DETECTED_ERROR($line){
			if(strlen(trim($line))==0){return false;}
			if(is_numeric($line)){$ERROR_CODE=intval($line);}
			if(!is_numeric($line)){if(preg_match("#^([0-9]+)\s+#",trim($line),$re)){$ERROR_CODE=$re[1];}}
			if(!isset($ERROR_CODE)){if(preg_match("#^([0-9]+)-#",trim($line),$re)){$ERROR_CODE=$re[1];}}
			if(!isset($ERROR_CODE)){$this->events("DETECTED_ERROR is not understood: `$line`", __FUNCTION__, __FILE__, __LINE__);}
			if(!is_numeric($ERROR_CODE)){return false;}
			if($ERROR_CODE==220){return false;}
			if($ERROR_CODE==250){return false;}
			if($ERROR_CODE==421){return true;}
			if($ERROR_CODE==554){return true;}
			if($ERROR_CODE==550){return true;}
			
		}
		

		function helo(){
			if(is_resource($this->connection)){$this->ERROR_SOCK_ARRAY=array();}
			
			if(is_resource($this->connection)){
					if($this->send_data('HELO '.$this->helo)){
						$error=trim($this->get_data());
						$datas=substr(trim($error), 0, 3);
						//$this->events("HELO $this->helo command: `$error` ($datas)", __FUNCTION__, __FILE__, __LINE__);
						if(is_numeric($error)){if($error==250){return true;}}
						if($datas==250){return true;}
					}
			}

			
			$this->events("HELO command failed, output: `$this->ERROR_SOCK` or `$error` or [".@implode("",$this->ERROR_SOCK_ARRAY)."]", __FUNCTION__, __FILE__, __LINE__);
			$this->PARSE_ERROR(trim(substr(trim($error), 3)));
			$this->errors[] = 'HELO command failed, output: ' . trim(substr(trim($error),3));
			return FALSE;
			
		}
		
		/**
        * Function to implement EHLO cmd
        */

		function ehlo(){
			if(is_resource($this->connection)
					AND $this->send_data('EHLO '.$this->helo)
					AND substr(trim($error = $this->get_data()), 0, 3) === '250' ){

				return TRUE;

			}else{
				$this->errors[] = 'EHLO command failed, output: ' . trim(substr(trim($error),3));
				return FALSE;
			}
		}
		
		/**
        * Function to implement RSET cmd
        */

		function rset(){
			if(is_resource($this->connection)
					AND $this->send_data('RSET')
					AND substr(trim($error = $this->get_data()), 0, 3) === '250' ){

				return TRUE;

			}else{
				$this->errors[] = 'RSET command failed, output: ' . trim(substr(trim($error),3));
				return FALSE;
			}
		}
		
		/**
        * Function to implement QUIT cmd
        */

		function quit(){
			if(is_resource($this->connection)
					AND $this->send_data('QUIT')
					AND substr(trim($error = $this->get_data()), 0, 3) === '221' ){

				fclose($this->connection);
				$this->status = SMTP_STATUS_NOT_CONNECTED;
				return TRUE;

			}else{
				$this->errors[] = 'QUIT command failed, output: ' . trim(substr(trim($error),3));
				return FALSE;
			}
		}
		
		/**
        * Function to implement AUTH cmd
        */

		function auth(){
			if(is_resource($this->connection)
					AND $this->send_data('AUTH LOGIN')
					AND substr(trim($error = $this->get_data()), 0, 3) === '334'
					AND $this->send_data(base64_encode($this->user))			// Send username
					AND substr(trim($error = $this->get_data()),0,3) === '334'
					AND $this->send_data(base64_encode($this->pass))			// Send password
					AND substr(trim($error = $this->get_data()),0,3) === '235' ){

				$this->authenticated = TRUE;
				return TRUE;

			}else{
				$this->errors[] = 'AUTH command failed: ' . trim(substr(trim($error),3));
				return FALSE;
			}
		}

		/**
        * Function that handles the MAIL FROM: cmd
        */
		
		function mail($from){

			if($this->is_connected()){
				if($this->send_data('MAIL FROM:<'.$from.'>')){
					$results=trim($this->get_data());
					$error=substr($results, 0, 3);
					if(trim($error)==null){$error=intval($results);}
					//$this->events("MAIL FROM:<$from> [$error] ($results)", __FUNCTION__, __FILE__, __LINE__);
					if(is_numeric($results)){if(!$this->DETECTED_ERROR($results)){return true;}}
					if(!$this->DETECTED_ERROR($error)){return true;}
					if($GLOBALS["VERBOSE"]){$this->events("MAIL FROM:<$from> is not a 250 -> FALSE", __FUNCTION__, __FILE__, __LINE__);}
				}				
				
			}
			
			$this->errors[] = $results;
			$this->PARSE_ERROR($results);
			return FALSE;
				
			}

		/**
        * Function that handles the RCPT TO: cmd
        */
		
		function rcpt($to){

			if($this->is_connected()){
					if($this->send_data('rcpt to: <'.$to.'>')){
						$datas=trim( $this->get_data());
						$error=substr($datas, 0, 2);
						if($error==25){return true;}
						
					}
			}
			$this->events("rcpt to: <$to> Result:`$datas` Proto:[$error] FAILED", __FUNCTION__, __FILE__, __LINE__);	
			$this->errors[] = $datas;
			$this->PARSE_ERROR($datas);
			return FALSE;
			
		}

		/**
        * Function that sends the DATA cmd
        */

		function data(){

			if($this->is_connected()
				AND $this->send_data('DATA')
				AND substr(trim($error = $this->get_data()), 0, 3) === '354' ){
				if($GLOBALS["VERBOSE"]){$this->events("DATA Success", __FUNCTION__, __FILE__, __LINE__);}
				return TRUE;

			}else{
				$this->errors[] = trim(substr(trim($error), 3));
				return FALSE;
			}
		}

		/**
        * Function to determine if this object
		* is connected to the server or not.
        */

		function is_connected(){

			return (is_resource($this->connection) AND ($this->status === SMTP_STATUS_CONNECTED));
		}

		/**
        * Function to send a bit of data
        */

		function send_data($data){

			if(is_resource($this->connection)){
				if($this->debug){$this->events("SEND:: \"$data\"", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);}
				//$this->events("Write: ".strlen($data)." Bytes",__CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				if(strlen($data)<100){
					$this->errors[]="Write: $data";
				}else{
					$this->errors[]="Write: ".strlen($data)." Bytes";
				}
				return fwrite($this->connection, $data.CRLF, strlen($data)+2);
				
			}else
				return FALSE;
		}

		/**
        * Function to get data.
        */

		function &get_data(){

			$return = '';
			$line   = '';
			$loops  = 0;
			$RESULTS=array();

			if(is_resource($this->connection)){
				while((strpos($return, CRLF) === FALSE OR substr($line,3,1) !== ' ') AND $loops < 100){
					$line    = fgets($this->connection, 512);
					if(trim($line)==null){$loops++;continue;}
					$RESULTS=$line;
					if($this->debug){$this->events("RECEIVE:: \"$line\"", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);}
					$this->ERROR_SOCK_ARRAY[]=$line;
					$return .= $line;
					$loops++;
				}
				//$this->events("Return: \"". trim(@implode(" ", $RESULTS))."\"", __CLASS__.'/'.__FUNCTION__, __FILE__, __LINE__);
				$ffErr=trim(@implode(" ", $RESULTS));
				if($ffErr<>null){
					$this->errors[]="Return \"$ffErr\"";
				}
				$this->ERROR_SOCK=trim(@implode(" ", $RESULTS));
				return $return;

			}else
				return FALSE;
		}

		/**
        * Sets a variable
        */
		
		function set($var, $value){

			$this->$var = $value;
			return TRUE;
		}

	} // End of class
?>