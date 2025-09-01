<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
// header("Access-Control-Allow-Credentials: true");
// header("Access-Control-Allow-Headers: Content-Type");
// header("Referrer-Policy: no-referrer-when-downgrade");
header("Content-Type: application/json; charset=UTF-8");


require_once '../../private_files/initialize.php';



$api=new call_api($db);


$result = $api->payment();
echo $result;

?>