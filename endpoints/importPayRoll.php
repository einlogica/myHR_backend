<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../private_files/initialize.php';
require_once '../../private_files/jwt.php';



if(decodeJWT()){
    
    $req = file_get_contents('php://input');

        
    $api=new call_api($db);

    $result = $api->importPayRoll($req);
    
    echo $result;
        
    
}    
else{
    echo "TokenFailed";
}    


?>
