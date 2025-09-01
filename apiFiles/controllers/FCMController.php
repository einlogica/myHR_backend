<?php

    require '/var/myhrFiles/php-jwt/vendor/autoload.php';
    use \Firebase\JWT\JWT;
    use \Firebase\JWT\Key;


    define('SERVICE_ACCOUNT_FILE', '/var/private_files/config/service-account.json');
    define('FCM_URL', 'https://fcm.googleapis.com/v1/projects/einlogicahr/messages:send');



    class FCMController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }


        public function updatefcm($data){

            if(!isset($data['usermobile']) || !isset($data['fcm'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $fcm=$data['fcm'];
            $device=$data['device'];
    
            $query = "SELECT * FROM `UserInfo` LEFT JOIN `fcmtocken` ON `UserInfo`.Mobile=`fcmtocken`.Mobile WHERE `UserInfo`.`Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
    
            if($rowCount!=0){
                $row = $stm->fetch(PDO::FETCH_ASSOC);
    
                if($row['fcmtocken']==null){
                    $query = "INSERT INTO `fcmtocken`(`Mobile`,`fcmtocken`) VALUES ('$usermobile','$fcm')";
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                }
                else if($row['fcmtocken']!=$fcm){
                    $query = "UPDATE `fcmtocken` SET `fcmtocken`='$fcm' WHERE `Mobile`='$usermobile'";
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                }
            }
        }
    
        
        // Function to generate access token using service account
        function generate_access_token($token) {
            $service_account = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);
    
            // Build JWT
            $now = time();
            $jwt_payload = array(
                'iss' => $service_account['client_email'],
                'sub' => $service_account['client_email'],
                'aud' => 'https://accounts.google.com/o/oauth2/token',
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'iat' => $now,
                'exp' => $now + 3600, // Token expires in 1 hour
            );
    
            // echo $service_account['private_key'];
    
            // Create JWT
            $jwt = JWT::encode($jwt_payload, $service_account['private_key'], 'RS256');
    
            // Request OAuth2 token
            $ch = curl_init('https://accounts.google.com/o/oauth2/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            )));
    
            $response = curl_exec($ch);
            // echo $response;
            curl_close($ch);
    
            if ($response === false) {
                die('Error requesting access token: ' . curl_error($ch));
            }
    
            $data = json_decode($response, true);
            return $data['access_token'];
        }
    
        // Function to send a notification via FCM
        function send_fcm_message($token,$title,$body) {
    
            // $token, $title, $body
            // $token=trim($data['token']);
            // $title=trim($data['title']);
            // $body=trim($data['body']);
    
            $access_token = $this->generate_access_token($token);
            $headers = array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            );
    
            $data = [
                'url' => 'https://play.google.com/store/apps/details?id=com.einlogica.einlogica_hr' // URL to be opened in the browser
            ];
    
            $notification = array(
                'message' => array(
                    'token' => $token,
                    'notification' => array(
                        'title' => $title,
                        'body' => $body,
                        // 'image' => 'https://myhr.einlogica.com/myHR3.png',
                        // 'icon' => 'myhr'
                        // 'data' => $data,
                    ),
                ),
            );
    
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, FCM_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
    
            $response = curl_exec($ch);
            // echo $response;
    
            if ($response === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
    
            curl_close($ch);
    
            // return $response;
        }
    
        function getManagerAdminFCM($emp,$usermobile){
            // $emp="Jilari";
            // $usermobile='9947999931';
            // $query = "SELECT * FROM `userinfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp' AND `Permission`='Admin'";
            // $stm = $this->conn->prepare($query);
            // $stm->execute();
            // $rowCount = $stm->rowCount();
            // if($rowCount==0){
                
                $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE ((`Mobile` IN (SELECT `Mobile` FROM `employeeinfo` WHERE `EmployeeID` IN (SELECT `ManagerID` FROM `employeeinfo` WHERE `Mobile`='$usermobile'))) OR (`Mobile` IN (SELECT `Mobile` FROM `userinfo` WHERE `Permission`='Admin' AND `Employer`='$emp' AND `Mobile` != '$usermobile'))) AND `Mobile`!='$usermobile'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                
                if($rowCount!=0){
                    
                    $tokens = array();
                    while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                        $tokens[] = $row['fcmtocken'];
                    }
                    return $tokens;
    
                }
                else{
                    return "Failed";
                }
            // }
            // else{
            //     return "Failed";
            // }   
        }
    
        function getUserFCM($usermobile){
            
            $query = "SELECT * FROM `userinfo` WHERE `Mobile`='$usermobile' AND `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount==0){
                $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE `Mobile`=$usermobile";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                
                if($rowCount!=0){
                    $row = $stm->fetch(PDO::FETCH_ASSOC);
                    return $row['fcmtocken'];
                }
                else{
                    return "Failed";
                }
            }
            else{
                return "Failed";
            } 
            
        }
    

        



    }


?>