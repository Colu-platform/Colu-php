<?php
require_once ('../vendor/autoload.php');
use Colu\ColuSDK\Colu;

// get info from request
if (! empty ( $_REQUEST ["username"] )) {
	$username = $_REQUEST ["username"];
} else {
	$username = false;
}

if (! empty ( $_REQUEST ["phone"] )) {
	$phonenumber = $_REQUEST ["phone"];
} else {
	$phonenumber = false;
}

if (! empty ( $_REQUEST ["qr"] )) {
	$qr = $_REQUEST ["qr"];
} else {
	$qr = false;
}

if (! empty ( $_REQUEST ["msg"] )) {
	$msg = $_REQUEST ["msg"];
} else {
	$msg = false;
}

$colu = new Colu ( 'my_company', 'testnet', 'cQQy71GeXGeFWnDtypas2roY2qrk3KWjJLCxoFqc2wibXr2wWxie' );

if ($qr) {
	// send the listener request
	if ($res = $colu->registerUserByCode ( $qr, $msg )) {
		echo json_encode ( $res, JSON_UNESCAPED_SLASHES );
	}
	else {
		echo json_encode ( array("Error" => $colu->error), JSON_UNESCAPED_SLASHES );
	}
} 
else {
	if ($phonenumber) {
		$message = $colu->createRegistrationMessage ( $username );
		
		if ($res = $colu->registerUserByPhonenumber ( $phonenumber, $message ))
			echo json_encode ( $res, JSON_UNESCAPED_SLASHES );
		else
			echo json_encode ( array (
					"Error" => $colu->error 
			), JSON_UNESCAPED_SLASHES );
	} else {
		// QR registration
		$message = $colu->createRegistrationMessage ( $username );
		
		// get QR code
		if ($res = $colu->createRegistrationQR ( $message )) {
			// return a QR image and data for the listener call
			echo json_encode ( $res, JSON_UNESCAPED_SLASHES );
		}
		else 
			echo json_encode(array("Error" => $colu->error));
	}
}
?>