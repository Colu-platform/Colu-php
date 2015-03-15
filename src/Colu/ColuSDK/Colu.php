<?php

namespace Colu\ColuSDK;

use Endroid\QrCode\QrCode;
use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

/**
 *
 * @author Eyal Alsheich
 *
 *
 */
class Colu {
	
	private $privateKey, $network, $companyName, $ecdsa;
	private $coluHost = 'https://secure.colu.co';
	private $debug;
	public $error;
	
	/**
	 * @param string $companyName
	 * @param string $network
	 * @param string $WIF
	 * @param boolean $debug
	 */
	function __construct($companyName, $network = "testnet", $WIF = "", $debug = false) {
		
		// debug flag
		$this->debug = $debug;
		
		// making an instance of ecdsa
		$this->ecdsa = new BitcoinECDSA ();
		
		$this->companyName = $companyName;
		
		if ($network === "testnet") {
			// use test network
			$this->network = "6f";
			$this->ecdsa->setNetworkPrefix ( "6f" );
		} else {
			// use bitcoin network
			$this->network = "00";
			$this->ecdsa->setNetworkPrefix ( "00" );
		}
		
		if ($WIF) {
			// use the WIF provided
			$key = $this->WIFToPrivateKey ( $WIF );
			$this->ecdsa->setPrivateKey ( $key ["key"] );
			$this->privateKey = $key ["key"];
		} else {
			// randomize a new key
			$this->ecdsa->generateRandomPrivateKey ();
			$this->privateKey = $this->ecdsa->getPrivateKey ();
		}
	}
	
	
	/**
	 * @param string $WIF
	 * @return array
	 */
	private function WIFToPrivateKey($WIF) {
		$decode = $this->ecdsa->base58_decode ( $WIF );
		return array (
				'key' => substr ( $decode, 2, 64 ),
				'is_compressed' => (((strlen ( $decode ) - 10) == 66 && substr ( $decode, 66, 2 ) == '01') ? true : false) 
		);
	}
	
	/**
	 * @return string
	 */
	public function getWIF() {
		return $this->ecdsa->getWif ();
	}
	
	
	/**
	 * @param string $username
	 * @param string $userId
	 * @param string $addressIndex
	 * @return boolean
	 */
	public function verifyUser($username, $userId, $addressIndex = 0) {
		// create a registration message
		$data = $this->createRegistrationMessage($username);
		
		// create a user and import the extended key
		$user = new User($userId);
		
		// add the address to the registration message
		$data["client_address"] = $user->getAddress($addressIndex);
		$data["token_details"] = 'token';
		
		$url = $this->coluHost."/check_token_address";
		
		// send the request
		if ($res = $this->sendRequest($url, $data)) {
			// parse response
			$body = json_decode($res,true);
			
			// verify the message
			if ($this->verifyMessage($data, $body["verified_client_signature"], $body["client_public_key"], $body["verified"])) {
				return true;
			}
			else {
				$this->error = "Error cannot verify message";
				return false;
			}
		}
		else {
			$this->error = "Error connecting to server";
		}
		
		return false;
	}
	
	/**
	 * @param string $code
	 * @param string $msg
	 * @return multitype:boolean NULL 
	 */
	public function registerUserByCode($code, $registrationMessage) {
		$url = $this->coluHost . "/start_user_registration_to_company_by_code";
		$params = array (
				"code" => $code 
		);
		
		if ($res = $this->sendRequest ( $url, $params )) {
			$body = json_decode ( $res, true );
			if ($user = $this->parseRegistrationBody ( $body )) {
				
				// create user object to hold the response
				$user = new User ( $body ["extended_public_key"] );
				
				// verify return message
				if ($this->verifyMessage ( $registrationMessage, $body ["verified_client_signature"], $user->getPublicKey (), $body ["verified"] )) {
					return array (
							"success" => true,
							"userId" => $user->getId () 
					);
				} else {
					$this->error = "failure to validate message";
				}
			} else {
				// / bad response from server
				$this->error = "Bad resopnse from server \n\r";
			}
		} else {
			// falied to connect to the server
			$this->error = "Failed to connect to server: " . $this->error . " \n\r";
		}
	}
	
	/**
	 * @param string $username
	 * @param string $phonenumber
	 * @return multitype:boolean NULL |boolean
	 */
	public function registerUserByPhonenumber($phonenumber, $registrationMessage) {
		// build the url
		$url = $this->coluHost . "/start_user_registration_to_company";
		
		// add the phone number to the request
		$registrationMessage ["phonenumber"] = $phonenumber;
		
		// send the request
		if ($res = $this->sendRequest ( $url, $registrationMessage )) {
			// check the response
			$body = json_decode ( $res, true );
			if ($user = $this->parseRegistrationBody ( $body )) {
				
				// create user object to hold the response
				$user = new User ( $body ["extended_public_key"] );
				
				// verify return message
				if ($this->verifyMessage ( $registrationMessage, $body ["verified_client_signature"], $user->getPublicKey (), $body ["verified"] )) {
					return array (
							"success" => true,
							"userId" => $user->getId () 
					);
				} else {
					$this->error = "failure to validate message";
				}
			} else {
				// / bad response from server
				$this->error = "Bad resopnse from server \n\r";
			}
		} else {
			// falied to connect to the server
			$this->error = "Failed to connect to server: " . $this->error . " \n\r";
		}
		
		return false;
	}
	
	/**
	 * @param array $data
	 * @param string $clientSignature
	 * @param string $clientPublicKey
	 * @param boolean $verified
	 * @return boolean
	 */
	private function verifyMessage($data, $clientSignature, $clientPublicKey, $verified) {
		$message = $data ["message"];
		$signature = $data ["signature"];
		
		if (! empty ( $data ["client_address"] ))
			$clientAddress = $data ["client_address"];
		else
			$clientAddress = false;
		
		$clientMessage = array (
				"message" => $message,
				"signature" => $signature,
				"verified" => $verified 
		);
		
		$hash = hash ( 'sha256', json_encode ( $clientMessage, JSON_UNESCAPED_SLASHES ) );
		
		if ($clientAddress) {
			if ($this->verifySignature ( $hash, $clientSignature, $clientPublicKey )) {
				return $this->ecdsa->getAddress ( $clientPublicKey ) == $clientAddress;
			}
			else {
				$this->error = "Erorr cannot verify message";
				return false;
			}
		}
		
		return $this->verifySignature ( $hash, $clientSignature, $clientPublicKey );
	}
	
	/**
	 * @param hash $hash
	 * @param string $clientSignature
	 * @param string $publicKey
	 * @return boolean
	 */
	private function verifySignature($hash, $clientSignature, $publicKey) {
		$sig = bin2hex ( base64_decode ( $clientSignature ) );
		$RS = $this->getRSFromSig ( $sig );
		
		return $this->ecdsa->checkSignaturePoints ( $publicKey, $RS ["R"], $RS ["S"], $hash );
	}
	
	/**
	 * @param string $url
	 * @param array $fields
	 * @return boolean|mixed
	 */
	private function sendRequest($url, $fields) {
		$fields_string = "";
		
		// url-ify the data for the POST
		foreach ( $fields as $key => $value ) {
			$fields_string .= urlencode ( $key ) . '=' . urlencode ( $value ) . '&';
		}
		
		rtrim ( $fields_string, '&' );
		
		// open connection
		$ch = curl_init ();
		
		// set the url, number of POST vars, POST data
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, count ( $fields ) );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields_string );
		
		// execute post
		$result = curl_exec ( $ch );
		
		if ($errno = curl_errno ( $ch )) {
			$error_message = curl_strerror ( $errno );
			$this->error = "cURL error ({$errno}):\n {$error_message}";
			return false;
		}
		
		return $result;
	}
	
	/**
	 * @return string
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}
	
	/**
	 * @param string $number
	 * @return string
	 */
	public static function hexEncode($number) {
		$hex = gmp_strval ( gmp_init ( $number, 10 ), 16 );
		return (strlen ( $hex ) % 2 != 0) ? '0' . $hex : $hex;
	}
	
	/**
	 * @param string $signature
	 * @return boolean|multitype:string 
	 */
	private function getRSFromSig($signature) {
		$signature = hex2bin ( $signature );
		if ('30' != bin2hex ( substr ( $signature, 0, 1 ) ))
			return false;
		
		$RLength = hexdec ( bin2hex ( substr ( $signature, 3, 1 ) ) );
		$R = bin2hex ( substr ( $signature, 4, $RLength ) );
		
		if (substr ( $R, 0, 2 ) == "00" and substr ( $R, 2, 1 ) > "7")
			$R = substr ( $R, 2 );
		
		$SLength = hexdec ( bin2hex ( substr ( $signature, $RLength + 5, 1 ) ) );
		$S = bin2hex ( substr ( $signature, $RLength + 6, $SLength ) );
		
		if (substr ( $S, 0, 2 ) == "00" and substr ( $S, 2, 1 ) > "7")
			$S = substr ( $S, 2 );
		
		return array (
				"R" => $R,
				"S" => $S 
		);
	}
	
	/**
	 * @param string $username
	 * @return multitype:string
	 */
	public function createRegistrationMessage($username) {
		
		// get 10 digit random number and hex it
		$rand = self::hexEncode ( rand ( 1111111111, 9999999999 ) );
		
		// make sure the time is UTC
		date_default_timezone_set ( "UTC" );
		$utcTS = ( string ) (time () * 1000);
		
		// build base message in JSON format
		$msg = array (
				"username" => $username,
				"timestamp" => $utcTS,
				"rand" => $rand 
		);
		$message = json_encode ( $msg );
		
		// sign the message with DER encoding
		$signature = $this->signMessageDER ( $message );
		
		// get the public key
		$pubkey = $this->ecdsa->getPubKey ();
		
		// build registration message fields
		$registrationMessage = array (
				"message" => $message,
				"company_public_key" => $pubkey,
				"signature" => $signature,
				"company_name" => $this->companyName 
		);
		
		return $registrationMessage;
	}
	
	/**
	 * @param array $message
	 * @return boolean|multitype:unknown string Ambigous <boolean, mixed> 
	 */
	public function createRegistrationQR($message) {
		// get URL from server
		$qrRegCode = $this->requestQRCode ( $message );
		$QRcodeURL = $this->coluHost . "/qr?qr=" . $qrRegCode;
		
		// make sure we got a message from the server
		if ($qrRegCode == false){
			$this->error = "Erorr could not recieve QR from server";
			return false;
		}
		
		// create the QR image URI
		$qrCode = new QrCode ();
		
		$qrCode->setText ( $QRcodeURL );
		$qrCode->setSize ( 300 );
		$qrCode->setPadding ( 10 );
		$qrCode->setErrorCorrection ( 'high' );
		$qrCode->setLabelFontSize ( 16 );
		
		// we send back the original message so that it can be sent back to the server for vrification
		// this can also be accomplished by using a session which i opted not to use here
		return array (
				"qr" => $qrCode->getDataUri (),
				"code" => $qrRegCode,
				"message" => $message 
		);
	}
	
	/**
	 * @param array $message
	 * @return boolean|string
	 */
	public function requestQRCode($message) {
		$message ["by_code"] = true;
		
		$qr = json_decode ( $this->sendRequest ( $this->coluHost . "/start_user_registration_to_company", $message ), true );
		
		if (! $qr or empty ( $qr ["code"] )) {
			$this->error = "Error retriving QR code from server";
			return false;
		}
		
		return $qr ["code"];
	}
	
	/**
	 * @param array $message
	 * @return string
	 */
	private function signMessageDER($message) {
		// make a hash from the message
		$msgHash = hash ( 'sha256', $message );
		
		// get r and s using the private key
		$points = $this->ecdsa->getSignatureHashPoints ( $msgHash );
		
		// pad r and s with '00' in case they are negative
		if (substr ( $points ["R"], 0, 1 ) > "7")
			$points ["R"] = "00" . $points ["R"];
		
		if (substr ( $points ["S"], 0, 1 ) > "7")
			$points ["S"] = "00" . $points ["S"];
			
		// DER signature encoding
		$signature = '02' . dechex ( strlen ( hex2bin ( $points ['R'] ) ) ) . $points ['R'] . '02' . dechex ( strlen ( hex2bin ( $points ['S'] ) ) ) . $points ['S'];
		$signature = '30' . dechex ( strlen ( hex2bin ( $signature ) ) ) . $signature;
		$signature = base64_encode ( pack ( 'H*', $signature ) );
		
		return $signature;
	}
	
	/**
	 * @param array $body
	 * @return array|boolean
	 */
	private function parseRegistrationBody($body) {
		if (is_array ( $body ) && ! empty ( $body ["extended_public_key"] )) {
			return $body;
		}
		
		return false;
	}
}

?>