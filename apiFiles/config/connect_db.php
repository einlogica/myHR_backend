
<?php

//$dns = "mysql:host=127.0.0.1;port=3306;dbname=u144195158_jilariHR";

$dns = "mysql:host=jilarihrmysql.mysql.database.azure.com;port=3306;dbname=u144195158_jilariHR";
$db_user = "u144195158_jilariHRUser";
$db_pass = "+PTrCO3i";

//$server_name = "localhost";
//$conn = mysqli_connect($server_name, $mysql_username, $mysql_password,$db_name);

$sslCaPath = "/var/private_files/config/DigiCertGlobalRootCA.crt.pem";

// Connection options
$options = [
    PDO::MYSQL_ATTR_SSL_CA => $sslCaPath,
];

try{
 $db = new PDO ($dns, $db_user, $db_pass, $options);
}catch( PDOException $e){
 $error = $e->getMessage();
 echo $error;
}


?>
