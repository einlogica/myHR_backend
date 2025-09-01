<?php


require '/var/myhrFiles/php-jwt/vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;



error_reporting(E_ALL);
ini_set('display_errors', '1');



    function decodeJWT(){
        $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
        $secretKey = 'jilaritechnologies-jwt-tocken';
	
        if($token){
            
            $token = str_replace('Bearer ', '', $token);
            try {
            $decoded = JWT::decode($token, new Key($secretKey,'HS256'));
            
            $currentTimestamp = time(); // Get the current timestamp

            // Check the expiration time
            if (isset($decoded->exp) && $decoded->exp > $currentTimestamp && isset($decoded->domain) && $decoded->domain === 'jilaritechnologies.com' ) {
                return true;
            } else {
                return false;
            }
            
            
            } catch (Exception $e) {
                return false;
            }
        }
        
        
    }


?>
