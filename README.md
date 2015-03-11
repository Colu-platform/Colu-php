# Colu-node
Colu platform, PHP SDK

## Install

Install Composer in your project:
```bash
    $curl -s http://getcomposer.org/installer | php
```
Create a `composer.json` file in your project root:
```code
    {
        "require": {
            "Colu/Colu": "1.0.1"
        }
    }
```
Install via Composer
```bash
    $php composer.phar install
```

## Generate keys for your company:
If you create an instance of the ```Colu``` class with only the company name, the ```privateKey``` will be generated randomly on your machine.
Your ```privateKey``` is what defines your company.

```php
require_once ('vendor/autoload.php');
use Colu\Colu;
$colu = new Colu('my_company', 'testnet');

// This is your private key, keep it safe!!!
echo 'WIF: '.$colu->getWIF();
```

## Create instance from an existing key:
When you want to use our module in your server you need to generate keys only once. After that you can create an instance of ```Colu``` with your key:

```php
require_once ('vendor/autoload.php');
use Colu\Colu;

$privateKey = 'cQQy71GeXGeFWnDtypas2roY2qrk3KWjJLCxoFqc2wibXr2wWxie'; // this is the WIF version of a private key

$colu = new Colu('my_company', 'testnet', $privateKey);
```

## Register a user using 2FA:
There are two methods for registering a user using 2FA, directly by using a Phonenumber or by generating a QR code and then having the user scan the QR with their phone.

In both methods you need to Create a registration message:

  ```php
  $username = 'bob';
  $registrationMessage = $colu->createRegistrationMessage($username);
  ```  
  
1. QR registration:
  ```php
  $qr = $colu->createRegistrationQR($registrationMessage);
  ```  
  This will return an array containing the QR and the message to be verified:
  ```php
	"qr" => $qrCode->getDataUri (), // this is the actual QR image
	"code" => $qrRegCode, // send this to the registerUserByCode function
	"message" => $message // send this to the registerUserByCode function 
  ```
  Send the registration request:
  ```php
  $colu->registerUserByCode ( $code, $message );
  ```
  This will send a push notification to the user mobile application that will wait until approval is recieved.

2. Phonenumber Registration:
  ```php
  $phonenumber = "+12323455673";
  $colu->registerUserByPhonenumber ( $phonenumber, $registrationMessage );
  ```
  This will send a push notification to the user mobile application that will wait until approval is recieved.
  
If successful both cases return an array:
```php
"success" => true,
"userId" => $user->getId () // this is the user ID
```
save the ```userId``` for future verifications.

## Verify user:
To verify a user:
```php
$username = 'bob';
$userId = 'tpubDCgCu2jpxrR7j9JwFQ959wSkNwPQFNQvJJMFnikg1Sb4tkDnBNYaS3Sc1BxKL71hk3jPkQStEY1VE9mTaQjF8kDfEhzxjWid7eVK5F7nWi5';

if ($colu->verifyUser($username, $userId, 0)) {
    echo "verified";
  }
  else {
    echo $colu->error;
    // something bad happened
  }
}
```
This will send a push to the user mobile application and prompt him to sign on your message, you will receive the user signature and verify it locally.
