<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '/var/private_files/initialize.php';
require_once '/var/private_files/jwt.php';



if(decodeJWT()){
    
    
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        $action=trim($_POST['action']);
        
        $api=new call_api($db);

        $result = $api->$action();
        
        echo $result;
    }
    

    
        
    
}    
else{
    echo "TokenFailed";
}    


?>
