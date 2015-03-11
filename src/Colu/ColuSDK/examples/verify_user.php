<?php
require_once ('../vendor/autoload.php');
use Colu\ColuSDK\Colu;

$privateKey = 'cQQy71GeXGeFWnDtypas2roY2qrk3KWjJLCxoFqc2wibXr2wWxie';

$colu = new Colu('my_company', 'testnet', $privateKey);

$username = 'bob';
$userId = 'tpubDCj6AQEUBWm7LKtX1RJAHKH4YXyJUkVbz3or6gEZ7XcmdvdW5i73NeK7PcqvkgcCWdiPo53m5r556P8ns2E2q7tisMDdtwnohTk96uqorcW';

if ($colu->verifyUser($username, $userId))
	echo "User verified";
else 
	echo $colu->error." User not verified";
?>