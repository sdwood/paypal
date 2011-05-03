<?php
/**
 * 
 * @name  paypal IPN
 * @package Modis
 * @version 1.0
 * class used to process paypal transactions
 * 
 * */
class paypalTrasation {
	
	/**3
	 * @var int
	 * the api key
	 */
	private $auth_token = 1213232; 
	/**
	 * @var array
	 * hold all errors 
	 */
	private $errors = array (); 
	/**
	 * @var bool true or false
	 * use to set whether or not to use ssl
	 */
	private $ssl = false; 
	/**
	 * @var bool
	 * do you want to use development?
	 */
	private $sandBox = false; 

	
	
	/**
	 * @param bool [$env] can be set to 
	 * sets the auth token for api
	 */
	
	public function SetEnv($env) 
	{
		$this->sandBox = $env;
	}

	
	/**
	 * sets the auth token for api
	 * @param int [$auth_token] paypal api key
	 * @return void
	 */
	public function set_auth_token($auth_token) 
	{
		$this->auth_token = $auth_token;
	}
	
	/**
	 * sets the auth token for api
	 * @param int [$auth_token] paypal api key
	 * @return array 
	 */
	private function processPDT() 
	{
		//just get the date and time of trasaction
		$dateTime = date ( "F j, Y, g:i a" );
		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-synch';
		
		$tx_token = $_GET ['tx'];
		$req .= "&tx=$tx_token&at=$this->auth_token";
		
		// post back to PayPal system to validate
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen ( $req ) . "\r\n\r\n";
		
		//check what env we are using?
		if($this->sandBox === true){
			$paypalUrl = "";
		}else{
			$paypalUrl = "";
		}
		
		
		//check if we want to use ssl or not 
		if ($this->ssl === true) {
			$fp = fsockopen ( 'ssl://www.paypal.com', 443, $errno, $errstr, 30 );
		} else {
			$fp = fsockopen ( 'www.paypal.com', 80, $errno, $errstr, 30 );
		}
		
		
		
		if (! $fp) {
			$errors [] = "Did not recieve Paypal post $errno back $errstr";
		} else {
			fputs ( $fp, $header . $req );
			// read the body data
			$res = '';
			$headerdone = false;
			while ( ! feof ( $fp ) ) {
				$line = fgets ( $fp, 1024 );
				if (strcmp ( $line, "\r\n" ) == 0) {
					// now all the headers have been sent
					$headerdone = true;
				} else if ($headerdone) {
					// header has been read. now read the contents
					$res .= $line;
				}
			}
			
			// parse the data
			$lines = explode ( "\n", $res );
			$keyarray = array ();
			//check the first line is success
			if (strcmp ( $lines [0], "SUCCESS" ) == 0) {
				for($i = 1; $i < count ( $lines ); $i ++) {
					list ( $key, $val ) = explode ( "=", $lines [$i] );
					$keyarray [urldecode ( $key )] = urldecode ( $val );
				}
				//payment was good insert data 
				$this->insertData ( $keyarray );
			
			} else if (strcmp ( $lines [0], "FAIL" ) == 0) {
				$errors [] = "Payment Failed! ";
			} else {
				$errors [] = "Did not recieve Paypal post back";
			}
			
			fclose ( $fp );
		}
	}
}