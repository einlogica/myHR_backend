<?php

require '/var/private_expense/php-jwt/vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

define('SERVICE_ACCOUNT_FILE', '/var/private_files/service-account.json');
define('FCM_URL', 'https://fcm.googleapis.com/v1/projects/einlogicahr/messages:send');




error_reporting(E_ALL);
ini_set('display_errors', '1');

class call_api{
    
    private $conn;
    
    public function __construct($db){
        $this->conn=$db;
    }

    public function __destruct(){
        if($this->conn){
            $this->conn=null;
        }
    }
    
    

    //===============================================================================================================================================================USERS
    
    public function checkUpdate(){

        $newApp='V1.004';
        $app=trim($_POST['app']);
        if($app==$newApp){
            return "FALSE";
        }
        else{
            return "TRUE";
        }
    }

    public function getNewApp(){
        
        $path= "../../private_files/App/myHR.apk";
        
        // echo "$path\n";
        
        $byte_array = file_get_contents($path);
        $data = base64_encode($byte_array);
        return "$data";
    }

    public function login_fun(){

        // $appVersion =appVersion;
        $secretKey = 'jilaritechnologies-jwt-tocken';
        // echo $appVersion;
        
        
        if(!isset($_POST['usermobile']) || !isset($_POST['userpass']) || !isset($_POST['app'])){
            return;
        }
       
        
        $usermobile=trim($_POST['usermobile']);
        $userpass=trim($_POST['userpass']);
        $app=trim($_POST['app']);
        // $emp=trim($_POST['emp']);
        
        if($userpass==='DEACT'){
            return;
        }

        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        
        // if($appVersion!=$app){
        //     return json_encode(array("Data"=>"App version check failed","Status"=>"Failed"));
        // }
        // else{
            
            $query = "SELECT `Pin` FROM `Userinfo` Where `Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                if($row['Pin']===$userpass || password_verify($userpass,$row['Pin'])){

                    $query = "UPDATE `UserInfo` SET `appversion`='$app' WHERE `Mobile`='$usermobile'";
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    
                    $query ="SELECT `UserInfo`.Name,`UserInfo`.Mobile,`UserInfo`.EmployeeID,`UserInfo`.Employer,`UserInfo`.Permission,`UserInfo`.resetpassword,`EmployeeInfo`.Department,`EmployeeInfo`.Position,`EmployeeInfo`.Manager,`EmployeeInfo`.ManagerID,`EmployeeInfo`.DOJ,`UserInfo`.Tocken,`EmployeeInfo`.ImageFile,`EmployeeInfo`.LeaveCount,`EmployeeInfo`.`Status` FROM `UserInfo` LEFT JOIN `EmployeeInfo` ON `UserInfo`.Mobile=`EmployeeInfo`.Mobile WHERE `UserInfo`.Mobile = '$usermobile' AND `EmployeeInfo`.`Status`!='INACTIVE'";
                
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    $rowCount = $stm->rowCount();

                    
                    if($rowCount!=0){

                        $row = $stm->fetch(PDO::FETCH_ASSOC);
            
                        $payload = array("domain" => "jilaritechnologies.com","exp" => time() + 15000);

                        $token = JWT::encode($payload, $secretKey,'HS256');

                        return json_encode(array("Data"=>$row,"Token"=>$token,"Status"=>"Success"));
                    }
                    else{
                        return json_encode(array("Data"=>"User Deactivated","Status"=>"Failed"));
                    }

                }
                else{
                    return json_encode(array("Data"=>"Credentials Failed","Status"=>"Failed"));
                }
            }
            else{
                return json_encode(array("Data"=>"Credentials Failed","Status"=>"Failed"));
            }

            
	               
            
        // }
        
        // $conn->close();

    }


    public function updatefcm(){

        if(!isset($_POST['usermobile']) || !isset($_POST['fcm'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $fcm=$_POST['fcm'];
        $device=$_POST['device'];

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
    function send_fcm_message($token, $title, $body) {

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

    public function versionNotifier(){

        // $tokens=$this->getManagerAdminFCM($emp,$usermobile);

        
        $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE `Mobile`='9847099931'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){
            // $row = $stm->fetch(PDO::FETCH_ASSOC);
            // $tokens= $row['fcmtocken'];
            // echo $tokens;
            $tokens = array();
            while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                $tokens[] = $row['fcmtocken'];
            }
        }
        else{
            return "Failed";
        }
        
        if($tokens!="Failed"){
            foreach ($tokens as $token) {
                $this->send_fcm_message($token,'New update available','Click here');
            }
        }

    }

    
    
    public function upload_image(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['file'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $file=$_POST['file'];
        $emp=$_POST['emp'];
        
        $target_dir ="../../private_files/Profile_Images/$emp/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $filename = $usermobile.".png";
        $target_file = $target_dir . $filename;
        $data = base64_decode($file);  
        
        if (file_put_contents($target_file, $data)) {
            
            // $query = "UPDATE `ExpenseUsers` SET `ImageFile`='$filename' WHERE `Mobile`='$usermobile' ";
            $query = "UPDATE `EmployeeInfo` SET `ImageFile`='$filename' WHERE `Mobile`='$usermobile' ";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            return "Successfully uploaded";
            
            
        }
        else{
            return "Failed to upload image";
        }
        
    }
    
    
    
    public function get_userdetails(){
        
        if(!isset($_POST['filter']) || !isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $filter=trim($_POST['filter']);
        $mobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        
        if($filter==='ALL'){
            // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,Permission,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers`";
            $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp'";
            
        }
        else if($filter==='MANAGERLIST'){
            // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers` WHERE `Permission` ='Manager' or `Permission` ='Admin' ";
            $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp' AND (`UserInfo`.`Permission` ='Manager' OR `UserInfo`.`Permission` ='Admin') ";
        }
        else if($filter==='MANAGER'){
            // $query = "SELECT ExpenseUsers.Mobile,ExpenseUsers.Name,ExpenseUsers.EmployeeID,ExpenseUsers.Department,ExpenseUsers.Position,ExpenseUsers.Manager,ExpenseUsers.ManagerID,ExpenseUsers.Permission,Att.Location,ExpenseUsers.LeaveCount,ExpenseUsers.LeaveBalance,ExpenseUsers.ImageFile FROM `ExpenseUsers` LEFT JOIN (SELECT Location,Mobile FROM `Attendance` WHERE `Date`='$CurrDate') as `Att` ON ExpenseUsers.Mobile=Att.Mobile WHERE ExpenseUsers.ManagerID in (SELECT EmployeeID FROM `ExpenseUsers` Where `Mobile`='$mobile')";
            $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission`,Att.Location,Att.Status FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` LEFT JOIN (SELECT Status,Location,Mobile FROM `Attendance` WHERE `Date`='$CurrDate') as `Att` ON `EmployeeInfo`.`Mobile`=Att.`Mobile` WHERE `EmployeeInfo`.`ManagerID` in (SELECT EmployeeID FROM `EmployeeInfo` Where `Mobile`='$mobile') AND `EmployeeInfo`.`Employer`='$emp'";
        }
        else if($filter === 'ONE'){
            // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,Permission,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers` WHERE `Mobile` ='$mobile' ";
            $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Mobile` ='$mobile' AND `EmployeeInfo`.`Employer`='$emp'";
        }
        

        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return "Failed";
        }
        
        
    }
    
    public function getProfile(){
        
        if(!isset($_POST['device']) || !isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $device=trim($_POST['device']);
        $mobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE `Tocken`='$device' AND `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();

        if($rowCount!=0){
            $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Mobile` ='$mobile' AND (`EmployeeInfo`.`Employer`='$emp' OR `EmployeeInfo`.`Employer`='Jilari')";
            
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
            
        }
        else{
            return "Failed";
        }
        
        
    }
    
    public function getReportees(){
        
       
        
        if(!isset($_POST['filter']) || !isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $filter=trim($_POST['filter']);
        $mobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);
        
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        if($filter==='ALL'){
            
            if(isset($_POST['date'])){
                $CurrDate=$_POST['date'];
            }
        
            $query = "SELECT `EmployeeInfo`.`Name`,`EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Position`,`Att`.`Status` AS AttStatus,`Att`.`Location`,`EmployeeInfo`.`Status` FROM `EmployeeInfo` LEFT JOIN (SELECT `Mobile`,`Status`,`Location` FROM `Attendance` WHERE `Date`='$CurrDate') AS Att ON `EmployeeInfo`.`Mobile`=`Att`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp' ORDER BY `EmployeeInfo`.`Status` ASC,`Att`.`Status` DESC,`EmployeeInfo`.`Name` ASC ";
            
        }
        
        else if($filter==='MANAGER'){
            
            if(isset($_POST['date'])){
                $CurrDate=$_POST['date'];
            }
            $query = "SELECT `EmployeeInfo`.`Name`,`EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Position`,`Att`.`Status` AS AttStatus,`Att`.`Location`,`EmployeeInfo`.`Status` FROM `EmployeeInfo` LEFT JOIN (SELECT `Mobile`,`Status`,`Location` FROM `Attendance` WHERE `Date`='$CurrDate') AS `Att` ON `EmployeeInfo`.`Mobile`=`Att`.`Mobile` WHERE `EmployeeInfo`.`Status`= 'ACTIVE' AND `EmployeeInfo`.`Employer`='$emp' AND `EmployeeInfo`.`ManagerID` in (SELECT EmployeeID FROM `EmployeeInfo` Where `Mobile`='$mobile') ORDER BY `EmployeeInfo`.`Name` ASC, `Att`.`Status` DESC";
        }
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }
        
        
    }
    
    
    
    public function add_user(){
        
        if(empty($_POST['user'])){
            return json_encode(array("Status"=>"Failed","Mess"=>"Invalid Data"));;
        }
        
        
        $user= json_decode($_POST['user'],true);
        
        // if(!isset($user['Mobile']) || !isset($user['Name']) || !isset($user['EmployeeID']) || !isset($user['Employer']) || !isset($user['Department']) || !isset($user['Position']) || !isset($user['Permission']) || !isset($user['Manager']) || !isset($user['ManagerID']) || !isset($user['LeaveCount']) || !isset($user['DOJ']) || !isset($user['Sex']) || !isset($user['DOB']) || !isset($user['AL1']) || !isset($user['AL2']) || !isset($user['AL3']) || !isset($user['Zip']) || !isset($user['BG']) || !isset($user['EmName']) || !isset($user['EmNum'])){
        if(!isset($user['Mobile']) || !isset($user['Name']) || !isset($user['EmployeeID']) || !isset($user['Employer']) || !isset($user['Department']) || !isset($user['Position']) || !isset($user['Permission']) || !isset($user['Manager']) || !isset($user['ManagerID']) || !isset($user['LeaveCount'])){
                
            return json_encode(array("Status"=>"Failed","Mess"=>"Incomplete Data"));
        }
        
        // echo $user['Mobile'];
        // echo $usermobile;
        
        // return;
        
        $query = "SELECT * FROM `UserInfo` WHERE Tocken = '$user[device]' AND Permission='Admin'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount>0){
            
            $query = "SELECT `Settings`.`Users`,`UI`.`Count` FROM `Settings` LEFT JOIN (SELECT Count(Mobile) AS Count, Employer FROM `UserInfo` GROUP BY `Employer`) AS `UI` ON `Settings`.`Employer`=`UI`.`Employer` WHERE `Settings`.`Employer`='$user[Employer]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if((int)$row['Users']>(int)$row['Count']){
                $query = "SELECT * FROM `UserInfo` WHERE Mobile = '$user[Mobile]'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                
                // echo $rowCount;
                
                if($rowCount==0){
                    
                    
                    // $query = "INSERT INTO `ExpenseUsers` (`Name`, `Mobile`, `EmployeeID`, `Manager`, `ManagerID`, `Position`, `Department`, `Pin`, `Permission`, `LeaveCount`, `LeaveBalance`) VALUES ('$user[Name]','$user[Mobile]','$user[EmployeeID]','$user[Manager]','$user[ManagerID]','$user[Position]','$user[Department]','1111','$user[Permission]','$user[LeaveCount]','$user[LeaveCount]')";
                    
                    $query = "INSERT INTO `EmployeeInfo` (`Name`, `Mobile`, `EmployeeID`,`Employer`, `DOJ`, `Manager`, `ManagerID`, `Position`,`Department`, `LeaveCount`, `ImageFile`, `Status`) VALUES ('$user[Name]','$user[Mobile]','$user[EmployeeID]','$user[Employer]','$user[DOJ]','$user[Manager]','$user[ManagerID]','$user[Position]','$user[Department]', '$user[LeaveCount]','Def','ACTIVE');
                                INSERT INTO `UserInfo` (`Name`, `Mobile`,`EmployeeID`,`Employer`,`Pin`, `Permission`, `resetpassword`) VALUES ('$user[Name]','$user[Mobile]','$user[EmployeeID]','$user[Employer]','1111','$user[Permission]','TRUE');
                                INSERT INTO `PersonalData` (`Name`,`Mobile`,`Sex`, `DOB`, `AddL1`, `AddL2`, `AddL3`, `Zip`, `BloodGroup`, `EmContactName`, `EmContactNum`, `BankName`, `AccNum`,`PAN`) VALUES ('$user[Name]','$user[Mobile]','$user[Sex]','$user[DOB]','$user[AL1]','$user[AL2]','$user[AL3]','$user[Zip]','$user[BG]','$user[EmName]','$user[EmNum]','$user[BankName]','$user[AccNo]','$user[PAN]');";
                    
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        return json_encode(array("Status"=>"Success","Mess"=>"User added successfully"));
                    }
                    else{
                        return json_encode(array("Status"=>"Failed","Mess"=>"Failed to add user"));
                    }
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"User mobile already exist"));
                }
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"User limit reached for this plan"));
            }
            
            
            
        }
    }
    
    
    public function updatePersonalInfo(){
        
        if(empty($_POST['user'])){
            return;
        }
        
        
        $user= json_decode($_POST['user'],true);
        
        if(!isset($user['Mobile']) || !isset($user['Sex']) || !isset($user['DOB']) || !isset($user['AL1']) || !isset($user['AL2']) || !isset($user['AL3']) || !isset($user['Zip']) || !isset($user['BG']) || !isset($user['EmName']) || !isset($user['EmNum']) || !isset($user['BankName']) || !isset($user['AccNo']) || !isset($user['PAN']) || !isset($user['UAN']) || !isset($user['ESI'])){
            return;
        }
        
        
        $query = "UPDATE `PersonalData` SET `Sex`='$user[Sex]',`DOB`='$user[DOB]',`AddL1`='$user[AL1]',`AddL2`='$user[AL2]',`AddL3`='$user[AL3]',`Zip`='$user[Zip]',`BloodGroup`='$user[BG]',`EmContactName`='$user[EmName]',`EmContactNum`='$user[EmNum]',`BankName`='$user[BankName]',`AccNum`='$user[AccNo]',`UAN`='$user[UAN]',`PAN`='$user[PAN]',`ESICNo`='$user[ESI]' WHERE `Mobile`='$user[Mobile]'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return "Details updated successfully";
        }
        else{
            return "Failed to update details";
        }
        
    }
    
    
    public function change_password(){
        
        
        if(!isset($_POST['reset']) || !isset($_POST['usermobile']) || !isset($_POST['newpass']) || !isset($_POST['id'])){
            return;
        } 
        
        
        $userpass=trim($_POST['newpass']);
        $usermobile=trim($_POST['usermobile']);
        $oldpass=trim($_POST['oldpass']);
        $id=trim($_POST['id']);
        $reset=trim($_POST['reset']);
        $emp=trim($_POST['emp']);

        $pin = password_hash($userpass, PASSWORD_DEFAULT);

            if($reset==='true'){
                    
                //process for password reset by admin
                $query = "SELECT * FROM `UserInfo` WHERE `Employer` = '$emp' AND Permission = 'Admin'";
                
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                
                // echo $rowCount;
                
                if($rowCount!=0){
                    $qry = "UPDATE `UserInfo` SET `Pin`='$pin',`resetpassword`='TRUE' WHERE `Mobile`='$usermobile'";
                    $stm = $this->conn->prepare($qry);  
                    if($stm->execute()===TRUE){
                        return "Completed";
                    }
                    else{
                        return "Failed";
                    }
                }
                else{
                    return "Failed";
                }

            }
            else{
        
                //process for password change after reset
                $query = "SELECT * FROM `UserInfo` WHERE Mobile = '$usermobile' AND resetpassword = 'TRUE' AND Employer = '$emp'";
                
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();

                if($rowCount!=0){
                    $qry = "UPDATE `UserInfo` SET `Pin`='$pin', `Tocken`='$id', `resetpassword`='FALSE' WHERE `Mobile`='$usermobile'";
                    $stm = $this->conn->prepare($qry);  
                    if($stm->execute()===TRUE){
                        return "Completed";
                    }
                }
                else{
                    
                    //process for password change by user
                    $query = "SELECT * FROM `UserInfo` WHERE Mobile = '$usermobile'";
                    
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    $rowCount = $stm->rowCount();
                    $row = $stm->fetch(PDO::FETCH_ASSOC);

                    if($oldpass===$row['Pin'] || password_verify($oldpass,$row['Pin'])){
                        $qry = "UPDATE `UserInfo` SET `Pin`='$pin' WHERE `Mobile`='$usermobile'";
                        $stm = $this->conn->prepare($qry);  
                        if($stm->execute()===TRUE){
                            return "Completed";
                        }
                    }   
                }
            }
    }
    
    
    public function searchDirectory(){
        
        if(!isset($_POST['filter']) || !isset($_POST['emp'])){
            return;
        } 
        
        
        $filter=trim($_POST['filter']);
        $emp=trim($_POST['emp']);
        
        // echo $filter;
        
        $query = "SELECT Name,Mobile,EmployeeID,Manager,Position,Department,ImageFile FROM `EmployeeInfo` WHERE Status='ACTIVE' AND Employer='$emp' AND (LOWER(Name) LIKE LOWER('%$filter%') OR LOWER(Department) LIKE LOWER('%$filter%') OR Mobile LIKE '%$filter%' OR LOWER(Position) LIKE LOWER('%$filter%'))";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return json_encode(array());
        }
    }
    
    public function updateBasicDetails(){
        
        if(!isset($_POST['manager']) || !isset($_POST['managerid']) || !isset($_POST['position']) || !isset($_POST['department']) || !isset($_POST['permission']) || !isset($_POST['mobile']) || !isset($_POST['doj']) || !isset($_POST['leave'])){
            return;
        }
        
        $manager=trim($_POST['manager']);
        $managerid=trim($_POST['managerid']);
        $position=trim($_POST['position']);
        $department=trim($_POST['department']);
        $permission=trim($_POST['permission']);
        $mobile=trim($_POST['mobile']);
        $doj=trim($_POST['doj']);
        $leave=trim($_POST['leave']);
        
        $query = "UPDATE `EmployeeInfo` SET `Manager`='$manager',`ManagerID`='$managerid',`Position`='$position',`Department`='$department',`DOJ`='$doj',`LeaveCount`='$leave' WHERE `Mobile`='$mobile'; UPDATE `UserInfo` SET `Permission`='$permission' WHERE `Mobile`='$mobile'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return "Details updated successfully";
        }
        else{
            return "Failed to update details";
        }
    }
    
    public function checkPersonalInfo(){
        
        //Used in profile page
        
        if(!isset($_POST['emp']) || !isset($_POST['mobile'])){
            return;
        } 
        
        
        $emp=trim($_POST['emp']);
        $mobile=trim($_POST['mobile']);
        
        // echo $mobile;
        // echo $device;
        
        $query = "SELECT `PersonalData`.* FROM `PersonalData` INNER JOIN `UserInfo` ON `PersonalData`.`Mobile`=`UserInfo`.`Mobile` WHERE `UserInfo`.`Mobile`='$mobile' AND `UserInfo`.`Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount==1){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            return json_encode($row);
            
        }
        else{
            return "Failed";
        }
    }
    
    public function deactivateEmployee(){
        if(!isset($_POST['device']) || !isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $device=trim($_POST['device']);
        $mobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);
        
        
        $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount!=0){
            $query = "SELECT * FROM `EmployeeInfo` WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount==1){
                $query = "UPDATE `EmployeeInfo` SET Status='INACTIVE' WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return "User Deactivated";
                }
                else{
                    return "Failed";
                }
            }
        
        }
        else{
            return "Authentication Failed";
        }
    }


    public function getDepartments(){
        
        if(!isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $usermobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);

        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";  
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();

        if($rowCount!=0){

            $query = "SELECT `Type` FROM `departments` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===True){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return json_encode(array());
            }
        }
        else{
            return json_encode(array());
        }
    }

    public function getPositions(){
        
        if(!isset($_POST['mobile']) || !isset($_POST['emp'])){
            return;
        } 
        
        $usermobile=trim($_POST['mobile']);
        $emp=trim($_POST['emp']);

        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";  
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();

        if($rowCount!=0){

            $query = "SELECT `Type` FROM `positions` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===True){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return json_encode(array());
            }
        }
        else{
            return json_encode(array());
        }
    }
    
    
    
    //===========================================================================================================================================================================ATTENDANCE

    public function getMonthlyAttendance(){

        if(!isset($_POST['usermobile']) || !isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        $emp=trim($_POST['emp']);

        date_default_timezone_set('Asia/Kolkata');
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        $pre_date = date('Y-m-d', strtotime("-10 days"));
        
        //Check user
        $query = "SELECT Permission FROM `UserInfo` WHERE `Mobile`='$usermobile' and `Permission`!='User'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $per = $row['Permission'];
        $rowCount = $stm->rowCount();

        if($per==='Admin'){
            $query = "SELECT Count(*) AS Count FROM `EmployeeInfo` WHERE `Employer`='$emp' and `Status`='ACTIVE'";
        }
        else{
            $query = "SELECT Count(*) AS Count FROM `EmployeeInfo` WHERE `Employer`='$emp' and `Status`='ACTIVE' AND ManagerID in (Select EmployeeID From `EmployeeInfo` WHERE `Mobile`='$usermobile')";
        }
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $employee = $row['Count'];
        
        if($rowCount!=0){
            if($per==='Admin'){
                $query = "SELECT Day(Date) AS Day, Count(CASE WHEN Status = 'Absent' THEN 1 END) AS AbsentCount, Count(CASE WHEN Status = 'Present' THEN 1 END) AS PresentCount, Count(CASE WHEN Status = 'Leave' THEN 1 END) AS LeaveCount, $employee AS Total FROM attendance WHERE Date>$pre_date AND Mobile in ( Select Mobile from userinfo Where Employer='$emp') GROUP BY Date";
            }
            else{
                $query = "SELECT Day(Date) AS Day, Count(CASE WHEN Status = 'Absent' THEN 1 END) AS AbsentCount, Count(CASE WHEN Status = 'Present' THEN 1 END) AS PresentCount, Count(CASE WHEN Status = 'Leave' THEN 1 END) AS LeaveCount, $employee AS Total FROM attendance WHERE Date>$pre_date AND Mobile in ( Select Mobile from employeeinfo Where Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM employeeinfo WHERE Mobile='$usermobile' AND `Employer`='$emp')) GROUP BY Date";
            }
            
            // return $query;
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }


    }
    
    
    public function get_attendanceStatus(){
        
        
        
        if(!isset($_POST['usermobile'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        $query = "SELECT Attendance.*,LeaveTracker.Days FROM `Attendance` LEFT JOIN `LeaveTracker` ON Attendance.Mobile=LeaveTracker.Mobile AND Attendance.Date=LeaveTracker.LeaveDate WHERE Attendance.Mobile = '$usermobile' AND Attendance.Date = '$CurrDate'";
            
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            return json_encode($row);
            
        }
        else{
            return "Pending";
        }
    }
    
    
    public function post_Attendance(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['posLat']) || !isset($_POST['posLong']) || !isset($_POST['location']) || !isset($_POST['type'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $posLat=trim($_POST['posLat']);
        $posLong=trim($_POST['posLong']);
        $location=trim($_POST['location']);
        $type=trim($_POST['type']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        
        if($type==='CheckIn'){
            
            $query = "SELECT * FROM `Attendance` WHERE Mobile = '$usermobile' AND Date = '$CurrDate'";
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();

            if($rowCount===0){
                $query = "INSERT INTO `Attendance` (`Mobile`, `Name`, `PosLat`, `PosLong`,`Date`,`InTime`, `PosLat2`, `PosLong2`,`OutTime`,`Status`,`Location`,`Flag`) VALUES ('$usermobile','$username','$posLat','$posLong','$CurrDate','$Time','0.00','0.00','00:00:00','Present','$location','false')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()){
                    return json_encode(array("Status"=>"Attendance applied","Mess"=>"Checkin has been applied"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
                } 
            }
            else{
                // return json_encode(array("Status"=>"Attendance applied","Mess"=>"Attendance already applied"));
                $query = "UPDATE `Attendance` SET `PosLat`='$posLat',`PosLong`='$posLong',`InTime`='$Time',`Location`='$location',`Status`= CASE WHEN `Status`='Holiday' THEN 'Present' ELSE `Status` END WHERE `Mobile`='$usermobile' AND `Date`='$CurrDate'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()){
                    return json_encode(array("Status"=>"Attendance updated","Mess"=>"Checkin has been updated"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
                }
            }

            
        }
        else if($type==='CheckOut'){
            $query = "UPDATE `Attendance` SET `PosLat2`='$posLat',`PosLong2`='$posLong',`OutTime`='$Time',`OutDate`='$CurrDate',`Duration`=TIMEDIFF(`OutTime`,`InTime`) WHERE `Mobile`='$usermobile' AND `Date`='$CurrDate'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()){
                return json_encode(array("Status"=>"Attendance applied","Mess"=>"Checkout has been applied"));
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
            }
        }

        
    }

    public function markAbsent(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['date']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $date=trim($_POST['date']);
        $emp=trim($_POST['emp']);

        $query = "UPDATE `Attendance` SET PosLat=0.0,PosLong=0.0,InTime='00:00:00',PosLat2=0.0,PosLong2=0.0,OutTime='00:00:00',Status='Absent',Location='Absent' WHERE Mobile='$usermobile' AND Date='$date'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()){
            return "Success";
        }
        else{
            return "$date";
        }

    }
    
    
    
    public function getDefLocation(){
        
        if(!isset($_POST['emp'])){
            return;
        }
        
        $emp = trim($_POST['emp']);
        
        $query = "SELECT * FROM Locations WHERE `Employer`='$emp'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return "Failed";
        }
    }

    public function saveLocations(){
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['location']) || !isset($_POST['lat']) || !isset($_POST['long']) || !isset($_POST['range'])){
            return;
        }

        $emp = trim($_POST['emp']);
        $usermobile = trim($_POST['usermobile']);
        $location = trim($_POST['location']);
        $lat = trim($_POST['lat']);
        $long = trim($_POST['long']);
        $range = trim($_POST['range']);

        $query = "SELECT * FROM `UserInfo` WHERE `Employer`='$emp' AND `Mobile`='$usermobile' AND `Permission`='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
   
        if($rowCount!=0){
            $query = "INSERT INTO `Locations` (`Employer`,`Location`,`PosLat`,`PosLong`,`Range`) VALUES ('$emp','$location','$lat','$long','$range')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===True){
                return "Success";
            }
            else{
                return "Failed";
            }
        }
        else{
            return "Failed";
        }
    }

    public function deleteLocations(){
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['id'])){
            return;
        }

        $emp = trim($_POST['emp']);
        $usermobile = trim($_POST['usermobile']);
        $id = trim($_POST['id']);
 

        $query = "SELECT * FROM `UserInfo` WHERE `Employer`='$emp' AND `Mobile`='$usermobile' AND `Permission`='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
   
        if($rowCount!=0){
            $query = "DELETE FROM `Locations` WHERE ID='$id' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===True){
                return "Success";
            }
            else{
                return "Failed";
            }
        }
        else{
            return "Failed";
        }
    }
    
    
    public function get_AttendanceData(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['month']) || !isset($_POST['year'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        
        // date_default_timezone_set('Asia/Kolkata');
    
        // $Time=date('His');
        // $CurrDate=date('Ymd');
        // $end_date = date('Y-m-d', strtotime("-60 days"));
        
        // echo $end_date;
        
        // $query = "SELECT * FROM `Attendance` WHERE Mobile = '$usermobile' AND Date > $end_date ORDER BY Date DESC, Time DESC";
        
        $query = "SELECT * FROM `Attendance` WHERE Mobile = '$usermobile' AND MONTH(Date)= $month AND YEAR(Date)=$year  ORDER BY Date DESC, InTime DESC";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
        
            return json_encode($row);
        }
        
    }
    
    
    
    
    
    public function get_AttSummary(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['month']) || !isset($_POST['year'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        
        //Find Leave Balance
        $query = "SELECT LeaveCount FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $leaveBal=$row["LeaveCount"];
        $leave = array("Location"=>"LeaveBal","Count"=>"$leaveBal");
        
        
        
        //Find number of Sundays
        date_default_timezone_set('Asia/Kolkata');
        setlocale(LC_TIME, 'en_IN');
        
        
        
        
        //Calculate number of sundays
        $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
        $lastDay = date('Y-m-t', strtotime($firstDay));
    
        $sundayCount = 0;
        $currentDate = strtotime($firstDay);
    
        while (date('Y-m-d', $currentDate) <= $lastDay) {
            if (date('l', $currentDate) === 'Sunday') {
                $sundayCount++;
            }
            $currentDate = strtotime('+1 day', $currentDate);
        }
    
        $sun = array("Location"=>"Off Day","Count"=>"$sundayCount");
        // echo $sundayCount;
        
        //Calculate no of days
        $days = date('t', strtotime($firstDay));
        $tot = array("Location"=>"Total","Count"=>"$days");
        // echo $days;
        
        
        // $arrayVar = array("Location" => "Sunday","Count" => 3);
        // array_push($row,$arrayVar);
        $query = "SELECT `Location`, COUNT(`Location`) as Count FROM `Attendance` WHERE Mobile = '$usermobile' AND MONTH(Date)= $month AND YEAR(Date)=$year AND Location='HalfDay'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount==1){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $halfDay= $row['Count']/2;
            $hd = array("Location"=>"HalfDay","Count"=>"$halfDay");
        }
        else{
            $halfDay=0;
        }
        
        
        $query = "SELECT `Location`, COUNT(`Location`) as Count FROM `Attendance` WHERE Mobile = '$usermobile' AND MONTH(Date)= $month AND YEAR(Date)=$year AND Location!='HalfDay' GROUP BY Location";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            array_push($row,$sun);
            array_push($row,$leave);
            if($halfDay>0){
                array_push($row,$hd);
            }
            array_push($row,$tot);
            
        
            return json_encode($row);
        }
    }
    
    
    // public function updateAttendance(){
        
    //     if(!isset($_POST['usermobile']) || !isset($_POST['date'])){
    //         return;
    //     }
        
    //     $usermobile=trim($_POST['usermobile']);
    //     $date=trim($_POST['date']);
        
    //     // echo $date;
    //     $formattedDate = date_format(date_create("$date"),"Y-m-d");
    //     // echo $formattedDate;
    //     // return;
        
    //     $query = "UPDATE `Attendance` SET Location = 'Regularized' WHERE Mobile = '$usermobile' AND Date='$formattedDate'";
    //     $stm = $this->conn->prepare($query);
    //     if($stm->execute()===TRUE){
    //         return "Regularization applied";   
    //     }
    //     else{
    //         return "Failed to apply regularization";
    //     }
        
    // }
    
    public function postRegularization(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['date']) || !isset($_POST['comments']) || !isset($_POST['regIn']) || !isset($_POST['regOut'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $date=trim($_POST['date']);
        $comments=trim($_POST['comments']);
        $regIn=trim($_POST['regIn']);
        $regOut=trim($_POST['regOut']);
        
        $formattedDate = date_format(date_create("$date"),"Y-m-d");

        $query = "SELECT * FROM `Regularization` WHERE Mobile = '$usermobile' AND Date = '$formattedDate'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();

        if($rowCount===0){
            $query = "INSERT INTO `Regularization` ( `Name`, `Mobile`, `Date`, `InTime`, `OutTime`, `Comments`) VALUES ('$username','$usermobile','$formattedDate','$regIn','$regOut','$comments')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                $query = "UPDATE `Attendance` SET `Flag` = 'true' WHERE `Mobile`='$usermobile' AND `Date`='$formattedDate'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){

                    $tokens=$this->getManagerAdminFCM($emp,$usermobile);
                    if($tokens!="Failed"){
                        foreach ($tokens as $token) {
                            $this->send_fcm_message($token,'Regilarization',$username.' sumbitted a request');
                        }
                    }

                    return "Request send successfully";
                }
                else{
                    return "Failed to update attendance data";
                }
                
            }
            else{
                return "Failed to request regularization";
            }
        }
        // else{
        //     $query = "UPDATE `Regularization` SET `InTime`='$regIn', `OutTime`='$regOut', `Comments`='$comments' WHERE `Mobile`='$usermobile' AND `Date`='$formattedDate'";
        //     $stm = $this->conn->prepare($query);
        //     if($stm->execute()===TRUE){
        //         $tokens=$this->getManagerAdminFCM($emp,$usermobile);
        //         if($tokens!="Failed"){
        //             foreach ($tokens as $token) {
        //                 $this->send_fcm_message($token,'Regularization',$username.' modified a regularization request');
        //             }
        //         }
        //         return "Request updated successfully";
        //     }
        //     else{
        //         return "Failed to update regularization";
        //     }
        // }
        
        
        
    }
    
    
    public function getRegularization(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        // echo $usermobile;
        
        $query = "SELECT `Permission` FROM `UserInfo` WHERE `Mobile`='$usermobile'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $permission = $row['Permission'];
        
        if($permission==='Admin'){
            $query = "SELECT * FROM `Regularization` WHERE Mobile in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp')";
        }
        else if($permission==='Manager'){
            $query = "SELECT * FROM `Regularization` WHERE Mobile in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
        }    
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);   
        }
        else{
            return json_encode(array("Status"=>"Failed","Mess"=>"Failed to fetch regularization list"));
        } 
    }
    
    
    public function approveRegularization(){
        // echo $status;
        // return;
        
        if(!isset($_POST['usermobile']) || !isset($_POST['date']) || !isset($_POST['status'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $date=trim($_POST['date']);
        $status=trim($_POST['status']);
        
        if($status==="Approved"){
            // $query = "UPDATE `Attendance` SET `Status` = 'Regularized', `Location`=(SELECT `Comments` FROM `Regularization` WHERE `Mobile` = '$usermobile' AND `Date`='$date'), `InTime`=(SELECT `InTime` FROM `Regularization` WHERE `Mobile` = '$usermobile' AND `Date`='$date'),`OutTime`=(SELECT `OutTime` FROM `Regularization` WHERE `Mobile` = '$usermobile' AND `Date`='$date') WHERE `Mobile` = '$usermobile' AND `Date`='$date'";
            
            $query ="UPDATE `Attendance` JOIN `Regularization` ON `Attendance`.`Mobile`=`Regularization`.`Mobile` AND `Attendance`.`Date`=`Regularization`.`Date` SET `Attendance`.`Status`='Present',`Attendance`.`Flag`='false',`Attendance`.`InTime`=`Regularization`.`InTime`,`Attendance`.`OutTime`=`Regularization`.`OutTime`,`Attendance`.`Comments`=`Regularization`.`Comments`,`Attendance`.`Location`= if(`Attendance`.`Location`='Absent','Regularized',`Attendance`.`Location`)  WHERE `Attendance`.`Mobile` = '$usermobile' AND `Attendance`.`Date`='$date' ";
            
        }
        else{
            
            $query = "UPDATE `Attendance` SET `Flag`='false' WHERE `Mobile` = '$usermobile' AND `Date`='$date'";
            
        }
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $query = "DELETE FROM `Regularization` WHERE `Mobile` = '$usermobile' AND `Date`='$date'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){

                $token=$this->getUserFCM($usermobile);
                $this->send_fcm_message($token,'Regularization '.$status,'Request for '.$date.' is '.$status);

                return json_encode(array("Status"=>"Success","Mess"=>"Successfully $status request")); 
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to approve regularization"));
            }
            
        }
        else{
            return json_encode(array("Status"=>"Failed","Mess"=>"Failed to approve regularization"));
        } 
        
        
        
    }
    
    
    
    
    //===========================================================================================================================================================================================EXPENSE
    


    public function getMonthlyExpense(){

        if(!isset($_POST['usermobile']) || !isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        $emp=trim($_POST['emp']);

        date_default_timezone_set('Asia/Kolkata');
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        //Check user
        $query = "SELECT Permission FROM `UserInfo` WHERE `Mobile`='$usermobile' and `Permission`!='User'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $per = $row['Permission'];
        $rowCount = $stm->rowCount();

        // $query = "SELECT Count(*) AS Count FROM `EmployeeInfo` WHERE `Employer`='$emp' and `Status`='ACTIVE'";
        // $stm = $this->conn->prepare($query);
        // $stm->execute();
        // $row = $stm->fetch(PDO::FETCH_ASSOC);
        // $employee = $row['Count'];
        
        if($rowCount!=0){
            if($per=='Admin'){
                $query = "(SELECT `Type`, Sum(`Amount`) AS Amount FROM expensetracker WHERE `Status`='Approved' AND `Type`!='Advance' AND `Type`!='Salary Advance' AND Month(Date)='$month' AND Year(Date)='$year' AND Mobile in ( Select Mobile from userinfo Where Employer='$emp') GROUP BY Type) UNION (SELECT 'Total' AS Type , IFNULL(SUM(`Amount`), 0) AS Amount FROM expensetracker WHERE `Status`='Approved' AND `Type`!='Advance' AND `Type`!='Salary Advance' AND Month(Date)='$month' AND Year(Date)='$year' AND Mobile in ( Select Mobile from userinfo Where Employer='$emp'))";
            }
            else{
                $query = "(SELECT `Type`, Sum(`Amount`) AS Amount FROM expensetracker WHERE `Status`='Approved' AND `Type`!='Advance' AND `Type`!='Salary Advance' AND Month(Date)='$month' AND Year(Date)='$year' AND Mobile in ( Select Mobile from EmployeeInfo Where Employer='$emp' AND ManagerID in (SELECT EmployeeID From userinfo WHERE Mobile='$usermobile')) GROUP BY Type) UNION (SELECT 'Total' AS `Type`, IFNULL(SUM(`Amount`), 0) AS Amount FROM expensetracker WHERE `Status`='Approved' AND `Type`!='Advance' AND `Type`!='Salary Advance' AND Month(Date)='$month' AND Year(Date)='$year' AND Mobile in ( Select Mobile from EmployeeInfo Where Employer='$emp' AND ManagerID in (SELECT EmployeeID From userinfo WHERE Mobile='$usermobile')))";
            }
            
            // return $query;
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }


    }


    public function getVehicle(){
        if(!isset($_POST['usermobile']) || !isset($_POST['emp'])){
            return;
        }

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);

        $query = "SELECT * FROM `UserInfo` WHERE  Mobile = '$usermobile' AND Employer ='$emp'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){

            $query = "SELECT * FROM `VehicleData` WHERE Employer = '$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
        }
        else{
            return "Failed";
        }


    }
    
    public function get_billImage(){
        
        if(!isset($_POST['filename']) || !isset($_POST['type']) || !isset($_POST['emp'])){
            return;
        }
        
        $filename=trim($_POST['filename']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        
        
        
        // echo "$filename\n";
        
        if($type==="Bill"){
            $path= "../../private_files/Bill_Images/$emp/".$filename;
        }
        else if($type==="Policy"){
            $path= "../../private_files/PolicyDocuments/$emp/".$filename;
        }
        else if($type==="Employer"){
            $path= "../../private_files/Icons/$filename.png";
        }
        else if($type==="Collection"){
            $path= "../../private_files/Collection/$emp/".$filename;
        }
        else if($type==="Profile"){
            $path= "../../private_files/Profile_Images/$emp/".$filename;
        }

        if(file_exists($path)){
            $byte_array = file_get_contents($path);
            $data = base64_encode($byte_array);
            return "$data";
        }
        else{
            return "Failed";
        }
        
        // echo "$path\n";
        
        
    }
    
    
    
    
    public function clear_bill(){
        
        
        if(!isset($_POST['billid']) || !isset($_POST['status']) || !isset($_POST['comments'])){
            return;
        }
        
        $billid=trim($_POST['billid']);
        $status=trim($_POST['status']);
        $comments=trim($_POST['comments']);
        
        $query = "SELECT Status FROM `ExpenseTracker` WHERE ID = '$billid'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        
        
        if($rowCount!=0){

            
            $qry = "UPDATE `ExpenseTracker` SET `Status`='$status', `Comments`='$comments' WHERE `ID`='$billid'";
            $stm = $this->conn->prepare($qry);  
            if($stm->execute()===TRUE){
                return "Completed";
            }
        }
        
  
    }
    
    
    
    public function delete_bill(){
        
        if(!isset($_POST['billid']) || !isset($_POST['emp'])){
            return;
        } 
        
        $billid=trim($_POST['billid']);
        $emp=trim($_POST['emp']);

        $query = "SELECT * FROM `ExpenseTracker` WHERE ID = '$billid'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
           $billStatus=$stm->fetch(PDO::FETCH_ASSOC); 
           $imageFile=$billStatus['Filename'];

           $file_pointer = "../../private_files/Bill_Images/$emp/$imageFile";
           if(file_exists($file_pointer)){

               if (!unlink($file_pointer)) {
                    return "$file_pointer cannot be deleted due to an error";
                }
           }
           
            $query = "DELETE FROM `ExpenseTracker` WHERE ID = '$billid'";
    
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Completed";
            } 
           
            
        }
        
    }

    public function addEmployeeAdvance(){

        if(!isset($_POST['id']) || !isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['date']) || !isset($_POST['amount'])){
            return;
        } 
        
        $id=trim($_POST['id']);
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $account=trim($_POST['account']);
        $location=trim($_POST['location']);
        $date=trim($_POST['date']);
        $amount=trim($_POST['amount']);
        $emp=trim($_POST['emp']);

        $query = "SELECT * from `UserInfo` WHERE Tocken = '$id' AND Employer='$emp' AND Permission='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount!=0){
            
            
            $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Item`, `Site`, `Date`, `Type`, `Amount`,`Status`) VALUES ('$usermobile','$username','$location','$account','$date','Advance','$amount','Approved')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Success";
            }
            else{
                return "Failed";
            }

        }
        else{
            return "Permission denied";
        }



    }
    
    
    public function updateExpense(){
        
        
        if(!isset($_POST['id']) || !isset($_POST['per']) || !isset($_POST['status']) || !isset($_POST['comments'])){
            return;
        } 
        
        $id=trim($_POST['id']);
        $per=trim($_POST['per']);
        $status=trim($_POST['status']);
        $comments=trim($_POST['comments']);
        
        
        if($status==='Rejected'){
            
            if($per == "Manager"){
                $query = "UPDATE `ExpenseTracker` SET `L1Status`='$status', `L1Comments`='$comments', `Status` = 'Rejected' WHERE `ID`='$id'";
            }
            else if($per == "Admin"){
               $query = "UPDATE `ExpenseTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Rejected' WHERE `ID`='$id'"; 
            }
            
        }
        else{
            
            if($per == "Manager"){
                $query = "UPDATE `ExpenseTracker` SET `L1Status`='$status', `L1Comments`='$comments', `Status` = 'L1 Approved' WHERE `ID`='$id'";
            }
            else if($per == "Admin"){
               $query = "UPDATE `ExpenseTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Approved' WHERE `ID`='$id'"; 
            }
            
        }
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){

            $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE `Mobile` IN (SELECT `Mobile` FROM `ExpenseTracker` WHERE `ID`='$id')";
            $stm = $this->conn->prepare($query);
               
            if($stm->execute()===TRUE){
                
                while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                    $token = $row['fcmtocken'];
                    $this->send_fcm_message($token,'Expense '.$status,$per.' has '.$status. ' your request');
                }
            }

            return "Success";
        }
        else{
            return "Failed";
        }
        
        
    }

    public function clearExpense(){
        
        
        if(!isset($_POST['id']) || !isset($_POST['per']) || !isset($_POST['status']) || !isset($_POST['comments'])){
            return;
        } 
        
        $id=trim($_POST['id']);
        $per=trim($_POST['per']);
        $status=trim($_POST['status']);
        $comments=trim($_POST['comments']);

        $bulkID = json_decode($id);
        $idValues = "'".implode("','", $bulkID)."'";
       

        $query = "UPDATE `ExpenseTracker` SET `Status`='$status', `FinRemarks`='$comments' WHERE `ID` in ($idValues)";
        // echo $query;

        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return "Success";
        }
        else{
            return "Failed";
        }
        
        
    }

    public function addDailyWages(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['labour']) || !isset($_POST['labourcount']) || !isset($_POST['amount'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $site=trim($_POST['site']);
        $labourname=trim($_POST['labour']);
        $labourcount=trim($_POST['labourcount']);
        $duration=trim($_POST['duration']);
        $amount=trim($_POST['amount']);
        $date=trim($_POST['date']);


        $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`, `LabourName`, `LabourCount`, `Duration`, `Date`, `Type`, `Amount`,`Status`) VALUES ('$usermobile','$username','$site','$labourname','$labourcount','$duration','$date','Daily-Wage','$amount','Applied')";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){

            $tokens=$this->getManagerAdminFCM($emp,$usermobile);
            if($tokens!="Failed"){
                foreach ($tokens as $token) {
                    $this->send_fcm_message($token,'Daily Wage - Rs '.$amount.'/-',$username.' sumbitted an expense claim');
                }
            }

            return "Success";
        }
        else{
            return "Failed";
        }
        
        
        
    }
    
    
    public function upload_bill(){
        
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['billamount'])){
            return;
        } 
        
        $emp=$_POST['emp'];
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $type=trim($_POST['type']);
        $site=trim($_POST['site']);
        $item=trim($_POST['item']);
        // $labourname=trim($_POST['labourname']);
        $fromLoc=trim($_POST['fromLoc']);
        $toLoc=trim($_POST['toLoc']);
        $km=trim($_POST['km']);
        $billno=trim($_POST['billno']);
        $billamount=trim($_POST['billamount']);
        $billdate=trim($_POST['billdate']);
        $file=$_POST['file'];
        $fileavailable=trim($_POST['fileavailable']);
        
        return $this->process_uploadBill($emp,$usermobile,$username,$type,$item,0,'NA',$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable);
        
        
        
    }
    
    
    private function process_uploadBill($emp,$usermobile,$username,$type,$item,$shop,$shopdesc,$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable){

        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Ymd');
        
        if($type==="Advance" || $type ==="Salary Advance"){
            $status="Approved";
        }
        else{
            $status="Applied";
        }

        if($km===""){
            $km="0";
        }
        
        // echo $status;
        
        $target_dir ="../../private_files/Bill_Images/$emp/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        if($usermobile!="" and $billamount != "" and $fileavailable != ""){
        // echo "details available\n";
        
        if($fileavailable=="true"){
            
            // echo "file available\n";
            // $imageFileType = strtolower(pathinfo($file["name"],PATHINFO_EXTENSION));
            // echo "$imageFileType\n";
            // $filename = $usermobile."-".$CurrDate.$Time.".".$imageFileType;
            
            $filename = $usermobile."-".$CurrDate.$Time.".jpeg";
            $target_file = $target_dir . $filename;
            $data = base64_decode($file);      
            
            // move_uploaded_file($file["tmp_name"], $target_file
            
              if (file_put_contents($target_file, $data)) {
                $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`,`Shop`,`ShopDesc`,`Item`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$shop','$shopdesc','$item','$billdate','$type','$billno','$billamount','$filename','$status')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){

                    $tokens=$this->getManagerAdminFCM($emp,$usermobile);
                    if($tokens!="Failed"){
                        foreach ($tokens as $token) {
                            $this->send_fcm_message($token,'Expense - Rs '.$billamount.'/-',$username.' sumbitted an expense claim');
                        }
                    }
                    
                    return json_encode(array("Status"=>"Success","Mess"=>"Image and Data Inserted"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Image inserted, Data insert failed"));
                }
                
              } else {
                return json_encode(array("Status"=>"Failed","Mess"=>"Image Insert Failed"));
              }
        
        }
        else{
                
                
                if($type==='Labour'){
                    $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`LabourName`,`Duration`,`LabourCount`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','NONE','$status')";
                }
                else{
                    $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`,`Shop`,`ShopDesc`,`Item`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$shop','$shopdesc','$item','$billdate','$type','$billno','$billamount','NONE','$status')";
                   
                }
                // $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','NONE','$status')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){

                    $tokens=$this->getManagerAdminFCM($emp,$usermobile);
                    if($tokens!="Failed" && $type!="Salary Advance"){
                        foreach ($tokens as $token) {
                            $this->send_fcm_message($token,'Expense - Rs '.$billamount.'/-',$username.' sumbitted an expense claim');
                        }
                    }

                    return json_encode(array("Status"=>"Success","Mess"=>"Data Inserted"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Data Insert Failed"));
                }
            }   
        
        }
    }


    public function upload_purchasebill(){
        
        // echo "point 1";
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['username']) || !isset($_POST['billamount'])){
            return;
        } 
        // echo "point 2";
        
        $emp=trim($_POST['emp']);
        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $type=trim($_POST['type']);
        $billno=trim($_POST['billno']);
        $billamount=trim($_POST['billamount']);
        $billdate=trim($_POST['billdate']);
        $file=$_POST['file'];
        $fileavailable=trim($_POST['fileavailable']);
        
        $item=trim($_POST['item']);
        $site=trim($_POST['site']);
        
        
        $id=trim($_POST['id']);
        
        $shop=trim($_POST['shop']);
        
        // echo "point 3";
        
        
        if($id===""){

            
            $l1=trim($_POST['l1']);
            $l2=trim($_POST['l2']);
            $l3=trim($_POST['l3']);
            $dist=trim($_POST['dist']);
            $phone=trim($_POST['phone']);
            $gst=trim($_POST['gst']);
            
            
            $query = "SELECT * from `BillerTracker` WHERE GST = '$gst' AND GST != '' AND Employer='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                echo json_encode(array("Status"=>"Failed","Mess"=>"Duplicate GST"));
                return;
            }
            else{

                date_default_timezone_set('Asia/Kolkata');
    
                $Time=date('H:i');
                $CurrDate=date('Ymd');

                if(strlen($gst)>0){
                    $query = "INSERT INTO `BillerTracker` (`Employer`,`ShopName`,`AddressL1`,`AddressL2`,`AddressL3`,`District`,`Phone`,`GST`,`CreateDate`,`CreateTime`,`CreateUser`,`CreateMobile`) VALUES ('$emp','$shop','$l1','$l2','$l3','$dist','$phone','$gst','$CurrDate','$Time','$username','$usermobile') ";
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        $query = "SELECT * from `BillerTracker` WHERE ShopName = '$shop' AND Phone= '$phone' AND AddressL1='$l1' AND AddressL2 = '$l2'";
                        $stm = $this->conn->prepare($query);
                        $stm->execute();
                        $row = $stm->fetch(PDO::FETCH_ASSOC);
                        $shopid= $row['ShopID'];  
                    }
                }
                else{
                    $shopid='0';
                }
            }
        }
        else{
            $shopid=$id;
            
        }
        
        if($shopid!=""){
            // echo $shopid;
            $status = $this->process_uploadBill($emp,$usermobile,$username,$type,$item,$shopid,$shop,$site,"","",0,$billno,$billamount,$billdate,$file,$fileavailable);
            $value = json_decode($status,true);
            // echo $status;
            if($value['Status']==="Success"){

                // $tokens=$this->getManagerAdminFCM($emp,$usermobile);
                // if($tokens!="Failed"){
                //     foreach ($tokens as $token) {
                //         $this->send_fcm_message($token,'Expense - Rs '.$billamount.'/-',$username.' sumbitted a purchase claim');
                //     }
                // }
                return json_encode(array("Status"=>"Success","Mess"=>"Purchase bill uploaded"));
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Purchase bill upload failed"));
            }
        }
        else{
            return json_encode(array("Status"=>"Failed","Mess"=>"Failed to add shop details"));
        }
    
    }

    
    
    
    public function get_userexpense(){
        
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['mon']) || !isset($_POST['year']) || !isset($_POST['type'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $mon=trim($_POST['mon']);
        $year=trim($_POST['year']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        $pre_date = date('Y-m-d', strtotime("-20 days"));
        
       
        
        if($type==='MAN'){
            // $query = "SELECT * FROM ExpenseTracker WHERE Date BETWEEN '$pre_date' AND '$CurrDate' AND Mobile in (SELECT Mobile FROM `ExpenseUsers` WHERE ManagerID in (SELECT EmployeeID FROM `ExpenseUsers` WHERE `Mobile`='$usermobile')) ORDER BY Date ASC";
            $query = "SELECT ExpenseTracker.*, BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Shop=BillerTracker.ShopID WHERE MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' AND ExpenseTracker.Mobile in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY ExpenseTracker.Date ASC";
        }
        else if($type==='ALL'){
            // $query = "SELECT * FROM ExpenseTracker WHERE Date BETWEEN '$pre_date' AND '$CurrDate' ORDER BY Date ASC";
            $query = "SELECT ExpenseTracker.*, BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Shop=BillerTracker.ShopID WHERE ExpenseTracker.Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' ORDER BY ExpenseTracker.Date ASC";
        }
        else if($type==='EMP'){
            // $query = "SELECT * FROM ExpenseTracker WHERE Mobile = '$usermobile' AND MONTH(Date) = '$mon' AND YEAR(Date)='$year' ORDER BY Date ASC";
            $query = "SELECT ExpenseTracker.*,BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Shop=BillerTracker.ShopID WHERE ExpenseTracker.Mobile = '$usermobile' AND ExpenseTracker.Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' ORDER BY ExpenseTracker.Date ASC";
        }
       
        // return $query;
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return json_encode(array());
        }
    }



    public function get_preexpense(){
        
        if(!isset($_POST['emp']) || !isset($_POST['usermobile']) || !isset($_POST['mon']) || !isset($_POST['year']) || !isset($_POST['type'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $mon=trim($_POST['mon']);
        $year=trim($_POST['year']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        // $Time=date('His');
        // $CurrDate=date('Y-m-d');
        // $pre_date = date('Y-m-d', strtotime("-20 days"));
        $firstDay = date('Y-m-01', strtotime("$year-$mon-01"));

        if($mon-1==0){
            $mon=12;
            $year=$year-1;
        }
        else{
            $mon=$mon-1;
        }

        $balance=0;
        $advance=0;

        //Get sum of amounts
        
        $query = "SELECT Sum(Amount) AS Total FROM ExpenseTracker WHERE `Type` not LIKE '%Advance%' AND `Status`!='Rejected' AND Mobile = '$usermobile' AND Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND Date<'$firstDay'";

        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $balance=$row['Total'];
            // echo "balance:";
            // echo $balance;
            // echo " ";
        }

        //Get Advance amount
        
        $query = "SELECT Sum(Amount) AS Advance FROM ExpenseTracker WHERE `Type`='Advance' AND Mobile = '$usermobile' AND Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND Date<'$firstDay'";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $advance=$row['Advance'];
            // echo "advance:";
            // echo $advance;
            // echo " ";

        }
        
        $bal = $advance-$balance;

        // echo "bal:";
        // echo $bal;
        return $bal;

    }

    
    public function getBiller(){
        
        if(!isset($_POST['emp']) || !isset($_POST['filter']) || !isset($_POST['type'])){
            return;
        } 
        
        $filter=trim($_POST['filter']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        
        if($type=="ID"){
            $query = "SELECT * FROM `BillerTracker` WHERE ID = '$filter'";
        }
        else{
            $query = "SELECT * FROM `BillerTracker` WHERE Employer='$emp' AND (LOWER(ShopName) LIKE LOWER('%$filter%') OR LOWER(GST) LIKE LOWER('%$filter%'))";
        }
        
        
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return json_encode(array());
        }
    }
    
    
    


    public function addBiller(){

        if(!isset($_POST['emp']) || !isset($_POST['shop']) || !isset($_POST['l1']) || !isset($_POST['dist']) || !isset($_POST['phone'])){
            return;
        } 
        
        $shop=trim($_POST['shop']);
        $l1=trim($_POST['l1']);
        $l2=trim($_POST['l2']);
        $l3=trim($_POST['l3']);
        $dist=trim($_POST['dist']);
        $phone=trim($_POST['phone']);
        $gst=trim($_POST['gst']);
        $div=trim($_POST['div']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        $username=trim($_POST['username']);
        $usermobile=trim($_POST['usermobile']);

        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i');
        $CurrDate=date('Ymd');

        $query = "INSERT INTO `BillerTracker` (`Employer`,`ShopName`,`AddressL1`,`AddressL2`,`AddressL3`,`District`,`Phone`,`GST`,`Division`,`Type`,`CreateDate`,`CreateTime`,`CreateUser`,`CreateMobile`) VALUES ('$emp','$shop','$l1','$l2','$l3','$dist','$phone','$gst','$div','$type','$CurrDate','$Time','$username','$usermobile') ";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return "Success";
        }
        else{
            return "Failed";
        }




    }


    public function getDistrict(){

        $state=trim($_POST['state']);

        $query = "SELECT `DistrictName` FROM `districtlist` WHERE `StateName` = '$state'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount!=0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            return json_encode($row);
        }
        else{
            return json_encode(array());
        }
    }

    //===============================================================================================================================================================================Collection


    public function getMaterialSummary(){

        if(!isset($_POST['emp']) || !isset($_POST['usermobile'])){
            return;
        } 

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);

        // date_default_timezone_set('Asia/Kolkata');
    
        // $Time=date('H:i');
        // $CurrDate=date('Ymd');
        $CurrDate=trim($_POST['date']);


        $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";

        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){

            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $per = $row['Permission'];

            if($per==='Admin'){
                $query="SELECT billertracker.division,billertracker.type,count(collectiontracker.ShopID) AS Shops,sum(collectiontracker.dryweight) AS dryweight,sum(collectiontracker.clothweight) AS clothweight,sum(collectiontracker.amount) AS rejAmount FROM collectiontracker LEFT JOIN billertracker ON collectiontracker.ShopID=billertracker.ShopID WHERE Date ='$CurrDate' AND collectiontracker.Mobile in (SELECT `Mobile` FROM `userinfo` WHERE `Employer`='$emp') GROUP BY billertracker.type, billertracker.division ORDER BY billertracker.division";
            }
            else{
                $query="SELECT billertracker.division,billertracker.type,count(collectiontracker.ShopID) AS Shops,sum(collectiontracker.dryweight) AS dryweight,sum(collectiontracker.clothweight) AS clothweight,sum(collectiontracker.amount) AS rejAmount FROM collectiontracker LEFT JOIN billertracker ON collectiontracker.ShopID=billertracker.ShopID WHERE Date ='$CurrDate' AND collectiontracker.Mobile in (SELECT `Mobile` FROM userinfo WHERE `Employer`='$emp') GROUP BY billertracker.type, billertracker.division ORDER BY billertracker.division";
            }
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                return json_encode($row);
            }
            else{
                return json_encode(array());
            }   
        }
        else{
            return json_encode(array("Message"=>"Failed"));
        }     
    }

    //"SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM u144195158_jilarihr.projectsites LEFT JOIN u144195158_jilarihr.activity ON projectsites.Type=activity.Site LEFT JOIN u144195158_jilarihr.billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='2024-08-21' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='2024-08-21' AND activity.Mobile in (SELECT Mobile FROM u144195158_jilarihr.userinfo WHERE Employer='WKerala') GROUP BY Site"

    public function getBillerSummary(){

        if(!isset($_POST['emp']) || !isset($_POST['usermobile'])){
            return;
        } 

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $CurrDate=trim($_POST['date']);

        // date_default_timezone_set('Asia/Kolkata');
    
        // $Time=date('H:i');
        // $CurrDate=date('Ymd');

        $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";

        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){

            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $per = $row['Permission'];

            if($per==='Admin'){
                $query="SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM projectsites LEFT JOIN activity ON projectsites.Type=activity.Site LEFT JOIN billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='$CurrDate' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='$CurrDate' AND activity.Mobile in (SELECT Mobile FROM userinfo WHERE Employer='$emp') GROUP BY Site";
            }
            else{
                $query="SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM projectsites LEFT JOIN activity ON projectsites.Type=activity.Site LEFT JOIN billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='$CurrDate' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='$CurrDate' AND activity.Mobile in (SELECT Mobile FROM userinfo WHERE Employer='$emp') GROUP BY Site";
            }
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                return json_encode($row);
            }
            else{
                return json_encode(array());
            }   
        }
        else{
            return json_encode(array("Message"=>"Failed"));
        }     
    }


    public function getCashSummary(){

        if(!isset($_POST['emp']) || !isset($_POST['usermobile'])){
            return;
        } 

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $CurrDate=trim($_POST['date']);

        // date_default_timezone_set('Asia/Kolkata');
    
        // $Time=date('H:i');
        // $CurrDate=date('Ymd');

        $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";

        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){

            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $per = $row['Permission'];

            if($per==='Admin'){
                $query="SELECT Item,Sum(Total) As Amount FROM collectiontracker WHERE `Mobile` in (SELECT `Mobile` FROM UserInfo WHERE `Employer`='$emp') AND Date='$CurrDate' AND Item!='Material' Group BY Item";
            }
            else{
                $query="SELECT Item,Sum(Total) As Amount FROM collectiontracker WHERE `Mobile` in (SELECT `Mobile` FROM UserInfo WHERE `Employer`='$emp') AND Date='$CurrDate' AND Item!='Material' Group BY Item";
            }
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                return json_encode($row);
            }
            else{
                return json_encode(array());
            }   
        }
        else{
            return json_encode(array("Message"=>"Failed"));
        }     
    }



    public function uploadCollection(){
        
        if(!isset($_POST['emp']) || !isset($_POST['username']) || !isset($_POST['usermobile']) || !isset($_POST['date']) || !isset($_POST['shopid'])){
            return;
        } 

        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i');
        $CurrDate=date('Ymd');

        $usermobile=trim($_POST['usermobile']);
        $username=trim($_POST['username']);
        $shopid=trim($_POST['shopid']);
        
        $shopname1=trim($_POST['shopname']);
        $shopname=str_replace("'", "''", $shopname1);

        $date=trim($_POST['date']);
        $dry=trim($_POST['dry']);
        $cloth=trim($_POST['cloth']);
        $amt=trim($_POST['amt']);
        $file=trim($_POST['image']);
        $emp=trim($_POST['emp']);
        $lat=trim($_POST['lat']);
        $long=trim($_POST['long']);
        $item=trim($_POST['item']);

        
        $dryPrice=0;
        if($item!='Yard'){
            $dryPrice=$dry*10;
        }
        $clothPrice=$cloth*25;
        $tot=$dryPrice+$clothPrice+$amt;

        if($item==='Cash'){
            $amt=0;
        }
        


        
        if($file!=""){
            
            $target_dir ="../../private_files/Collection/$emp/";
        
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            
            $filename = $shopid."-".$CurrDate.$Time.".jpeg";
            $target_file = $target_dir . $filename;
            $data = base64_decode($file);      
            
              if (file_put_contents($target_file, $data)) {
            
                $query = "INSERT INTO `collectiontracker` (`Mobile`,`Name`, `ShopID`,`ShopName`,`Date`,`Time`,`DryWeight`,`ClothWeight`,`DryPrice`,`ClothPrice`, `Amount`, `Filename`,`Lat`,`Long`,`Total`,`Item`) VALUES ('$usermobile','$username','$shopid','$shopname','$date','$Time','$dry','$cloth','$dryPrice','$clothPrice','$amt','$filename','$lat','$long','$tot','$item')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return json_encode(array("Status"=>"Success","Mess"=>"Image and Data Inserted"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Image inserted, Data insert failed"));
                }
                
              } else {
                return json_encode(array("Status"=>"Failed","Mess"=>"Image Insert Failed"));
              }
        }
        else{
            $query = "INSERT INTO `collectiontracker` (`Mobile`,`Name`, `ShopID`,`ShopName`,`Date`,`Time`,`DryWeight`,`ClothWeight`,`DryPrice`,`ClothPrice`, `Amount`, `Filename`,`Lat`,`Long`,`Total`,`Item`) VALUES ('$usermobile','$username','$shopid','$shopname','$date','$Time','$dry','$cloth','$dryPrice','$clothPrice','$amt','NA','$lat','$long','$tot','$item')";
            // echo $query;
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return json_encode(array("Status"=>"Success","Mess"=>"Data Inserted"));
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Data Insert Failed"));
            }
        }

    }

    public function getCollection(){
        if(!isset($_POST['usermobile']) || !isset($_POST['per']) || !isset($_POST['date']) || !isset($_POST['emp'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $per = trim($_POST['per']);
        $date=trim($_POST['date']);
        // $month = trim($_POST['month']);
        $emp = trim($_POST['emp']);

        if($per==="User"){
            $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND `Mobile` ='$usermobile'";
        }
        else if($per==="Manager"){
            $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND (`Mobile` ='$usermobile' OR `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')))";
        }
        else if($per==="Admin"){
            $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE Employer='$emp')";
        }
        // return $query;
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }
    }


    public function deleteCollection(){
        if(!isset($_POST['id']) || !isset($_POST['emp'])){
            return;
        } 

        $billid=trim($_POST['id']);
        $emp=trim($_POST['emp']);
        $usermobile=trim($_POST['usermobile']);

        $query = "SELECT * FROM `collectiontracker` WHERE ID = '$billid' AND $usermobile in (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer` = '$emp')";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
           $billStatus=$stm->fetch(PDO::FETCH_ASSOC); 
           $imageFile=$billStatus['Filename'];
           if(strlen($imageFile)>5){
               $file_pointer = "../../private_files/Collection/$emp/$imageFile";
               if(file_exists($file_pointer)){
                    if (!unlink($file_pointer)) {
                        return "$file_pointer cannot be deleted due to an error";
                    }
               }
               $query = "DELETE FROM `collectiontracker` WHERE ID = '$billid'";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return "Collection has been removed";
                }
                else{
                    return "Failed to remove collection";
                }
               
           }
           else{
                $query = "DELETE FROM `collectiontracker` WHERE ID = '$billid'";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return "Collection has been removed";
                } 
                else{
                    return "Failed to remove collection";
                }
           }
            
        }



    }
    
    
    
    //===============================================================================================================================================================================LEAVE
    
    
    
    
    public function getLeave(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['year']) || !isset($_POST['type']) || !isset($_POST['emp'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        $year=trim($_POST['year']);
        $type = trim($_POST['type']);
        $emp = trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Ymd');

        //Fetch the Weekoff day from Settings
        $query = "SELECT `WeekOff`,`LeaveStructure` FROM `Settings` WHERE `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $weekoff=$row['WeekOff'];
        $leavestr=$row['LeaveStructure'];
        
        if($type==='MAN'){
            
            // $query = "SELECT * FROM `LeaveTracker` WHERE YEAR(LeaveDate)=YEAR('$CurrDate') AND `Mobile` in (SELECT Mobile FROM `ExpenseUsers` WHERE ManagerID in (SELECT EmployeeID FROM `ExpenseUsers` WHERE `Mobile`='$usermobile')) ORDER BY ID DESC LEFT JOIN (SELECT `Mobile`,`LeaveCount` FROM `ExpenseUsers`) AS `User` ON `LeaveTracker`.`Mobile`=`User`.`Mobile`";
            
            // $query = "SELECT `LeaveTracker`.*,`User`.`LeaveCount`-`LeaveTracker`.`LOP` AS `HIS` FROM `LeaveTracker` LEFT JOIN (SELECT `Mobile`,`LeaveCount` FROM `EmployeeInfo`) AS `User` ON `LeaveTracker`.`Mobile`=`User`.`Mobile` WHERE YEAR(`LeaveTracker`.LeaveDate)=YEAR('$CurrDate') AND `LeaveTracker`.`Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer = '$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY `LeaveTracker`.`LeaveDate` DESC ";
            
            $query = "SELECT * FROM `LeaveTracker` WHERE YEAR(`LeaveDate`)='$year' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer = '$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY `LeaveDate` DESC ";
            
        }
        else if($type==='ALL'){
            // $query = "SELECT * FROM `LeaveTracker` WHERE YEAR(LeaveDate)=YEAR('$CurrDate') ORDER BY ID DESC";
            // $query ="SELECT `LeaveTracker`.*,`User`.`LeaveCount`-`LeaveTracker`.`LOP` AS `HIS` FROM `LeaveTracker` LEFT JOIN (SELECT `Mobile`,`LeaveCount` FROM `EmployeeInfo`) AS `User` ON `LeaveTracker`.`Mobile`=`User`.`Mobile` WHERE `LeaveTracker`.`Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveTracker`.`LeaveDate`)=YEAR('$CurrDate') ORDER BY `LeaveTracker`.`LeaveDate` DESC";
            
            $query ="SELECT * FROM `LeaveTracker` WHERE `Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveDate`)='$year' ORDER BY `LeaveDate` DESC";

        }
        else{
            // $query = "SELECT * FROM `LeaveTracker` WHERE Mobile = '$usermobile' AND YEAR(LeaveDate)=YEAR('$CurrDate')";
            // $query = "SELECT `LeaveTracker`.*,`User`.`LeaveCount`-`LeaveTracker`.`LOP` AS `HIS` FROM `LeaveTracker` LEFT JOIN (SELECT `Mobile`,`LeaveCount` FROM `EmployeeInfo`) AS `User` ON `LeaveTracker`.`Mobile`=`User`.`Mobile` WHERE `LeaveTracker`.  `Mobile` = '$usermobile' AND `LeaveTracker`.`Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveTracker`.`LeaveDate`)='$year' ORDER BY `LeaveTracker`.`LeaveDate` DESC";
            
            if($leavestr=="YEARLY"){
                $query = "SELECT * FROM `LeaveTracker` WHERE `Mobile` = '$usermobile' AND `Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveDate`)='$year' ORDER BY `LeaveDate` DESC";

            }
            else{
                $month = trim($_POST['month']);
                $query = "SELECT * FROM `LeaveTracker` WHERE `Mobile` = '$usermobile' AND `Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveDate`)='$year' AND MONTH(`LeaveDate`)='$month' ORDER BY `LeaveDate` DESC";
            }
        }
        
        
        $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
        
        
        
    }
    
    public function deleteLeave(){
        
        if(!isset($_POST['id']) || !isset($_POST['type'])){
            return;
        } 
        
        $id=trim($_POST['id']);
        $type=trim($_POST['type']);
        
        $query = "SELECT * FROM `LeaveTracker` WHERE ID = '$id'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $halfDay = $row['Days'];
        $mobile = $row['Mobile'];
        $status = $row['Status'];
        $leaveDate=$row['LeaveDate'];
        // $location=$row['Location'];

        //Fetch the Weekoff day from Settings
        $query = "SELECT `WeekOff`,`LeaveStructure` FROM `Settings` WHERE `Employer` IN (SELECT `Employer` FROM USerInfo WHERE `Mobile`='$mobile')";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row1 = $stm->fetch(PDO::FETCH_ASSOC);
        $weekoff=$row1['WeekOff'];
        $leavestr=$row1['LeaveStructure'];

        // echo $leavestr;
        
        
        if(($row['L1Status']==='' && $row['L2Status']==='') || $type ==='MAN' || $status === 'Rejected'){
            
            
            $query = "DELETE FROM `LeaveTracker` WHERE ID = '$id'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
 
                if($leavestr==="YEARLY"){
                    $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$mobile' AND `Status`!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$leaveDate') ORDER BY `LeaveDate` ASC";
                }
                else{
                    $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$mobile' AND `Status`!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$leaveDate') AND MONTH(`LeaveDate`)=MONTH('$leaveDate') ORDER BY `LeaveDate` ASC";
                }
                // $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$mobile' AND `Status`!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$leaveDate') ORDER BY `LeaveDate` ASC";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
                if($leavestr==="YEARLY"){
                    $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$mobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$mobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$leaveDate');
                             UPDATE `attendance` SET `Status`='Present' WHERE `Location`!='Leave' AND `Mobile`='$mobile' AND `Date`='$leaveDate';
                             DELETE FROM `attendance` WHERE `Location`='Leave' AND `Mobile`='$mobile' AND `Date`='$leaveDate';";
                }
                else{
                    $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$mobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$mobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$leaveDate') AND MONTH(`LT`.`LeaveDate`)=MONTH('$leaveDate');
                             UPDATE `attendance` SET `Status`='Present' WHERE `Location`!='Leave' AND `Mobile`='$mobile' AND `Date`='$leaveDate';
                             DELETE FROM `attendance` WHERE `Location`='Leave' AND `Mobile`='$mobile' AND `Date`='$leaveDate';";
                }

                // echo $query;

                // $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$mobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$mobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$leaveDate')";
                $stm1 = $this->conn->prepare($query);
                if($stm1->execute()===true){
                    return json_encode(array("Status"=>"Success","Mess"=>"Leave request deleted"));
                }
                else{
                    return json_encode(array("Status"=>"Success","Mess"=>"Leave deletion failed"));
                }
                
                
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to delete"));
            }
        }
        else{
            
            // if($status === 'Rejected'){
            //     $query = "UPDATE `LeaveTracker` SET `Status`='Cancel Req' WHERE ID = '$id'";
            // }
            $query = "UPDATE `LeaveTracker` SET `Status`='Cancel Req' WHERE ID = '$id'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return json_encode(array("Status"=>"Success","Mess"=>"Leave cancellation requested"));
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to request cancellation"));
            } 
        }
        
    }
   
    
    
    public function postLeave(){
        
        if(!isset($_POST['username']) || !isset($_POST['usermobile']) || !isset($_POST['days']) || !isset($_POST['leavedate']) || !isset($_POST['comments'])){
            return;
        }
        
        $username=trim($_POST['username']);
        $usermobile=trim($_POST['usermobile']);
        $days=trim($_POST['days']);
        $leavedate=trim($_POST['leavedate']);
        $comments=trim($_POST['comments']);
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
 
        
        $bulkDates = json_decode($leavedate);
        $dateValues = "'".implode("','", $bulkDates)."'";
        
        $query = "SELECT * FROM `LeaveTracker` WHERE `Mobile`='$usermobile' AND `LeaveDate` IN ($dateValues)";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount===0){


            //Fetch the Weekoff day from Settings
            $query = "SELECT `WeekOff`,`LeaveStructure` FROM `Settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $weekoff=$row['WeekOff'];
            $leavestr=$row['LeaveStructure'];
            
            
            $query = "INSERT INTO `LeaveTracker` (`Mobile`, `Name`, `LeaveDate`, `Days`,`LOP`, `AppliedTime`, `AppliedDate`,`Comments`,`Status`) VALUES ";
            $values = [];
            if($days<=1){
                foreach ($bulkDates as $data) {
                    $v = "('$usermobile','$username','$data','$days','0','$Time','$CurrDate','$comments','Applied')";
                    $values[] = $v;
                }
            }
            else{
                foreach ($bulkDates as $data) {
                    $v = "('$usermobile','$username','$data','1','0','$Time','$CurrDate','$comments','Applied')";
                    $values[] = $v;
                }
            }
            
       
            $query .= implode(",", $values);
            
            //echo $query;
            // return;
        
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){


                if($leavestr=="YEARLY"){
                    $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$usermobile' AND Status!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$bulkDates[0]') ORDER BY `LeaveDate` ASC";
                }
                else{
                    $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$usermobile' AND Status!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$bulkDates[0]') AND MONTH(`LeaveDate`)=MONTH('$bulkDates[0]') ORDER BY `LeaveDate` ASC";
                }
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();

                $ld = ($dateValues);
                
                if($leavestr=="YEARLY"){
                    $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$usermobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$usermobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$bulkDates[0]')";
                }
                else{
                    $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$usermobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$usermobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$bulkDates[0]') AND MONTH(`LT`.`LeaveDate`)=MONTH('$bulkDates[0]')";
                }
                // $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$usermobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$usermobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$bulkDates[0]')";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();

                $tokens=$this->getManagerAdminFCM($emp,$usermobile);
                if($tokens!="Failed"){
                    foreach ($tokens as $token) {
                        $this->send_fcm_message($token,'Leave',$username.' sumbitted a leave request');
                    }
                }
                
                return json_encode(array("Status"=>"Success","Mess"=>"Leave has been applied"));
                
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed"));
            }
            
            
        }
        else{
            return json_encode(array("Status"=>"Success","Mess"=>"Request exist"));
        }
    }
    
    
    public function updateLeave(){
        
        if(!isset($_POST['id']) || !isset($_POST['per']) || !isset($_POST['status']) || !isset($_POST['comments'])){
            return;
        }

        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        $id=trim($_POST['id']);
        $per=trim($_POST['per']);
        $status=trim($_POST['status']);
        $comments=trim($_POST['comments']);
        // $emp=trim($_POST['emp']);
        
        
        $bulkID = json_decode($id);
        $idValues = "'".implode("','", $bulkID)."'";
        
        
        if($status==="Rejected"){

            //Fetch the Weekoff day from Settings
            // $query = "SELECT `WeekOff`,`LeaveStructure` FROM `Settings` WHERE `Employer`='$emp'";
            // $stm = $this->conn->prepare($query);
            // $stm->execute();
            // $row = $stm->fetch(PDO::FETCH_ASSOC);
            // $weekoff=$row['WeekOff'];
            // $leavestr=$row['LeaveStructure'];

            
            if($per == "Manager"){
                $query = "UPDATE `LeaveTracker` SET `L1Status`='$status', `L1Comments`='$comments', `Status`='Rejected',`LOP`=0 WHERE `ID` IN ($idValues);
                UPDATE attendance RIGHT JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` SET attendance.`Status`='Absent',attendance.`Location`='Absent' WHERE leavetracker.`ID` in ($idValues) AND attendance.`InTime`='00:00:00';
                UPDATE attendance RIGHT JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` SET attendance.`Status`='Present' WHERE leavetracker.`ID` in ($idValues) AND attendance.`InTime`!='00:00:00'; 
                DELETE attendance FROM attendance INNER JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` WHERE attendance.`Date`='$CurrDate' AND leavetracker.`ID` in ($idValues) AND attendance.`InTime`='00:00:00'";
            }
            else if($per == "Admin"){
               $query = "UPDATE `LeaveTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Rejected',`LOP`=0 WHERE `ID` IN ($idValues);
               UPDATE attendance RIGHT JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` SET attendance.`Status`='Absent',attendance.`Location`='Absent' WHERE leavetracker.`ID` in ($idValues) AND attendance.`InTime`='00:00:00';
                UPDATE attendance RIGHT JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` SET attendance.`Status`='Present' WHERE leavetracker.`ID` in ($idValues) AND attendance.`InTime`!='00:00:00';
               DELETE attendance FROM attendance INNER JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` WHERE attendance.`Date`='$CurrDate' AND leavetracker.`ID` in ($idValues) AND attendance.`InTime`='00:00:00'"; 
            }
            // echo $query;
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                // $query="DELETE FROM attendance RIGHT JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` WHERE leavetracker.`ID` in ($idValues)";
                
                //update serial numbering for determining LOP
                // if($leavestr=="YEARLY"){
                //     $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$usermobile' AND YEAR(`LeaveDate`)=YEAR('$bulkDates[0]') ORDER BY `LeaveDate` ASC";
                // }
                // else{
                //     $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$usermobile' AND YEAR(`LeaveDate`)=YEAR('$bulkDates[0]') AND MONTH(`LeaveDate`)=MONTH('$bulkDates[0]') ORDER BY `LeaveDate` ASC";
                // }
                // $stm1 = $this->conn->prepare($query);
                // $stm1->execute();


                //Send push notification
                $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues) AND `Mobile` NOT IN (SELECT `Mobile` FROM `UserInfo` WHERE `Permission`='Admin'))";
                $stm = $this->conn->prepare($query);
                
                if($stm->execute()===TRUE){
                    while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                        $token = $row['fcmtocken'];
                        $this->send_fcm_message($token,'Leave '.$status,$per.' has '.$status. ' your request');
                    }
                }

                            
                return "Success";
            }
            else{
                return "Failed";
            }
            
        }
        else{
            if($per == "Manager"){
            $query = "UPDATE `LeaveTracker` SET `L1Status`='$status', `L1Comments`='$comments', `Status`='L1 Approved' WHERE `ID` IN ($idValues)";
            }
            else if($per == "Admin"){

                // $query = "SELECT * from `LeaveTracker` WHERE `ID` in ($idValues) AND `LeaveDate`='$CurrDate'";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                // $chkCount = $stm->rowCount();
                // // if approving on same day
                // if($chkCount>0){
                //     $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `Mobile`,`Name`,'0.00','0.00','$CurrDate','00:00','0.00','0.00','00:00','Leave','Leave' FROM `LeaveTracker` WHERE `ID` in ($idValues) and `LeaveDate`='$CurrDate' AND `Mobile` NOT IN (SELECT Mobile FROM attendance WHERE Date='$CurrDate');
                //               UPDATE `Attendance` ";
                //     $stm = $this->conn->prepare($query);
                //     $stm->execute();
                // }

                $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `Mobile`,`Name`,'0.00','0.00','$CurrDate','00:00','0.00','0.00','00:00','Leave','Leave' FROM `LeaveTracker` WHERE `ID` in ($idValues) and `LeaveDate`='$CurrDate' AND `Mobile` NOT IN (SELECT Mobile FROM attendance WHERE Date='$CurrDate')";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                $query = "UPDATE `Attendance` AS `A` INNER JOIN `LeaveTracker` AS `L` ON `A`.`Mobile`=`L`.`Mobile` AND `A`.`Date`=`L`.`LeaveDate` SET `A`.`Status`= 'Leave' WHERE `L`.`ID` IN ($idValues);
                          UPDATE `Attendance` AS `A` INNER JOIN `LeaveTracker` AS `L` ON `A`.`Mobile`=`L`.`Mobile` AND `A`.`Date`=`L`.`LeaveDate` AND `L`.`Days`=0.5 SET `A`.`Status`= 'HalfDay' WHERE `L`.`ID` IN ($idValues)";
                        //    UPDATE `attendance` INNER JOIN leavetracker ON attendance.`Mobile`=leavetracker.`Mobile` AND attendance.`Date`=leavetracker.`LeaveDate` AND leavetracker.`Days`=0.5 SET attendance.`Status`='HalfDay' WHERE leavetracker.`ID` in ($idValues)";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                $query = "UPDATE `LeaveTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Approved' WHERE `ID` IN ($idValues)"; 
            }
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){

                //Send push notification
                $query = "SELECT `fcmtocken` FROM `fcmtocken` WHERE `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues) AND `Mobile` NOT IN (SELECT `Mobile` FROM `UserInfo` WHERE `Permission`='Admin'))";
                $stm = $this->conn->prepare($query);
                
                if($stm->execute()===TRUE){
                    while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                        $token = $row['fcmtocken'];
                        $this->send_fcm_message($token,'Leave '.$status,$per.' has '.$status. ' your request');
                    }
                }
                

                return "Success";
            }
            else{
                return "Failed";
            }
        }
        
    }
    
    
    
    //==================================================================================================================================================================================ACTIVITY
    
    
    
    public function getActivity(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['date']) || !isset($_POST['type']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $date=trim($_POST['date']);
        $type=trim($_POST['type']);
        $emp=trim($_POST['emp']);
        
        
        if($type==='EMP' || $type==='EMP-MAN'){
            $query = "SELECT * FROM `Activity` WHERE Mobile = '$usermobile' AND ActivityDate='$date'  ORDER BY ID DESC";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row);
            }
        }
        else if($type==='MAN'){
            
            // date_default_timezone_set('Asia/Kolkata');
    
            // $Time=date('His');
            // $CurrDate=date('Y-m-d');
            
            $query = "SELECT * FROM `Activity` WHERE `ActivityDate`='$date' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY ID DESC";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row);
            }
        }
        else if($type==='ALL'){
            // echo "$type";
            $query = "(SELECT * FROM `Activity` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `ActivityDate`='$date' ORDER BY ID DESC) UNION (SELECT '0' as `ID`, `Mobile` ,`Name`, 'Pending' as Type,'' as Site,'false' as Drive,'0' as StartKM,'0' as EndKM,'0.00' as PosLat,'0.00' as PosLong,'Pending' as Activity,'$date' as ActivityDate,'$date' as Date,'00:00' as Time,'' as Customer,'' as Remarks FROM `EmployeeInfo` WHERE `Employer`='$emp' and `Status`='ACTIVE' and `Mobile` not in (SELECT `Mobile` FROM `Activity` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `ActivityDate`='$date')); ";
            // echo "$query";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row);
            }
        }
        
        
    }
    
    
     public function post_Activity(){
         
        if(!isset($_POST['username']) || !isset($_POST['usermobile']) || !isset($_POST['activity']) || !isset($_POST['date'])){
            return;
        }
         
        $username=trim($_POST['username']);
        $usermobile=trim($_POST['usermobile']);
        $type=trim($_POST['type']);
        $site=trim($_POST['site']);
        $date=trim($_POST['date']);
        $drive=trim($_POST['drive']);
        $sKM=trim($_POST['sKM']);
        $eKM=trim($_POST['eKM']);
        $lat=trim($_POST['lat']);
        $long=trim($_POST['long']);
        $activity=trim($_POST['activity']);
        $cust=trim($_POST['cust']);
        $custno=trim($_POST['custno']);
        
        // echo "Hi";
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        // echo $drive;
        // return;
        
        
        $query = "INSERT INTO `Activity` (`Mobile`, `Name`,`Type`, `Site`, `Drive`, `StartKM`, `EndKM`, `PosLat`, `PosLong`,`ActivityDate`, `Date`, `Time`,`Customer`,`Remarks`) VALUES ('$usermobile','$username','$type','$site','$drive','$sKM','$eKM','$lat','$long','$date','$CurrDate','$Time','$cust','$custno')";
        // return $query;
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return json_encode(array("Status"=>"Success"));
        }
        else{
            return json_encode(array("Status"=>"Failed"));
        }
        
    }
    
    public function delete_Activity(){
        
        if(!isset($_POST['id']) || !isset($_POST['usermobile'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $id=trim($_POST['id']);
        
        $query = "DELETE FROM `Activity` WHERE `Mobile`='$usermobile' AND `ID`='$id'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()){
            return json_encode(array("Status"=>"Success"));
        }
        else{
            return json_encode(array("Status"=>"Failed"));
        }
        
    }
    
    
    public function get_DriveActivity(){
        
        if(!isset($_POST['usermobile'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        
        $value=0;
        $query = "SELECT * FROM `Activity` WHERE Mobile = '$usermobile' AND `Drive`='true' AND (`StartKM`='$value' OR `EndKM`='$value') ORDER BY ID DESC";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount>0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return json_encode(array("Status"=>"NothingPending","FileName"=>"NONE"));
        }
    }
    
    public function update_DriveActivity(){
        
        if(!isset($_POST['id']) || !isset($_POST['sKM']) || !isset($_POST['eKM'])){
            return;
        }
        
        $id=trim($_POST['id']);

        $sKM=trim($_POST['sKM']);
        $eKM=trim($_POST['eKM']);
        
        
        $query = "UPDATE `Activity` SET `StartKM`='$sKM' , `EndKM` = '$eKM' WHERE `ID` = '$id'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return json_encode(array("Status"=>"Success"));
        }
        else{
            return json_encode(array("Status"=>"Failed"));
        }
        
    }
    
    
    
    public function getActivityType(){
        
       if(!isset($_POST['emp'])){
            return;
        }
        
        $emp =trim($_POST['emp']);
        
        $query = "SELECT * FROM `ActivityType` WHERE Employer = '$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount>0){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }
        
        
    }

    public function getCustomerType(){
        
        if(!isset($_POST['emp'])){
             return;
         }
         
         $emp =trim($_POST['emp']);
         
         $query = "SELECT * FROM `customertype` WHERE Employer = '$emp'";
         $stm = $this->conn->prepare($query);
         $stm->execute();
         $chkCount = $stm->rowCount();
         if($chkCount>0){
             $row = $stm->fetchall(PDO::FETCH_ASSOC);
             return json_encode($row);
         }
         else{
             return "Failed";
         }
         
         
     }
    
    
    
    
    //=========================================================================================================================================================================================== PaySlip
    
    public function getSalaryStructure(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['device'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $device=trim($_POST['device']);
        

        $check = "false";
        
        $query = "SELECT * from `UserInfo` WHERE (`Tocken` = '$device' AND `Mobile`='$usermobile') OR (`Permission` = 'Admin' AND `Tocken` = '$device')";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount!=0){
            // echo json_encode(array("Status"=>"Failed","Mess"=>"Invalid User"));
            
            $check = "true";
        }


        
        
        if($check === 'true'){
            $query = "SELECT * FROM `SalaryStructure` WHERE `Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $chkCount = $stm->rowCount();
            if($chkCount==1){
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return json_encode(array("Name"=>"NoName"));
            }
            
            // if($stm->execute()===TRUE){
            //     // $chkCount = $stm->rowCount();
            //     // echo $chkCount;
            //     $row = $stm->fetch(PDO::FETCH_ASSOC);
            //     return json_encode($row);
            // }
            
            
        }
        else{
            return json_encode(array("Name"=>"NoName"));
        }
        
        
        
    }
    
    
    public function updateSalary(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['device']) || !isset($_POST['basic'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $device=trim($_POST['device']);
        $basic=trim($_POST['basic']);
        $special    =trim($_POST['special']);
        $hra=trim($_POST['hra']);
        $ta=trim($_POST['ta']);
        $da=trim($_POST['da']);
        $incentive=trim($_POST['incentive']);
        $pf=trim($_POST['pf']);
        $esic=trim($_POST['esic']);
        $protax=trim($_POST['protax']);
        
        $query = "SELECT * from `UserInfo` WHERE `Tocken` = '$device' AND `Permission` = 'Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount!=0){
            
            $query = "SELECT * from `SalaryStructure` WHERE `Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $chkCount = $stm->rowCount();
            if($chkCount!=0){
                
                $query = "UPDATE `SalaryStructure` SET `Basic`='$basic',`Allowance`='$special',`HRA`='$hra',`TA`='$ta',`Incentive`='$incentive',`DA`='$da',`PF`='$pf',`ESIC`='$esic',`ProTax`='$protax' WHERE `Mobile`='$usermobile'";
            }
            else{
                
                $query = "SELECT * FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $name = $row['Name'];
                $empid = $row['EmployeeID'];  
                
                $query = "INSERT INTO `SalaryStructure` (`Name`, `Mobile`, `EmployeeID`, `Basic`, `Allowance`, `HRA`, `TA`, `DA`, `Incentive`,`PF`,`ESIC`,`ProTax`) VALUES ('$name','$usermobile','$empid','$basic','$special','$hra','$ta','$da','$incentive','$pf','$esic','$protax')";
                
            }
            
            
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Successfully updated";
            }
            else{
                return "Failed to update";
            }
        } 
        else{
            return "Invalid user";
        }
        
        
        
    }
    
    
    public function generatePayroll(){
        
        if(!isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        $lop=trim($_POST['lop']);
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        $users=trim($_POST['users']);

        $bulkUsers = json_decode($users);
        $userValues = "'".implode("','", $bulkUsers)."'";
        
        $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND Tocken='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            $query = "DELETE FROM `PayRollTemplate` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Mobile` in ($userValues) AND `Employer`='$emp')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            
            // $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
            // $lastDay = date('Y-m-t', strtotime($firstDay));
            // $totalDays = date('t', strtotime("$year-$month-01"));
            
            // //Find number of Sundays
            // date_default_timezone_set('Asia/Kolkata');
            // setlocale(LC_TIME, 'en_IN');
            
            // echo $firstDay;
            // echo " ";
            // echo $lastDay;
            // echo " ";
            
            // $sundayCount = 0;
            // $currentDate = strtotime($firstDay);
            
            
        
            // while (date('Y-m-d', $currentDate) <= $lastDay) {
            //     if (date('l', $currentDate) === 'Sunday') {
            //         $sundayCount++;
            //     }
            //     $currentDate = strtotime('+1 day', $currentDate);
            // }

            
    
            // $query = "INSERT INTO `PayRollTemplate` SELECT '$month','$year',SalaryStructure.Name,SalaryStructure.Mobile,'$lastDay',COALESCE(`Leave`.Count,0) AS `LeaveDays`,if(`LeaveCount`.LeaveCount-COALESCE(`Leave`.Count,0)<0,COALESCE(`Leave`.Count,0)-`LeaveCount`.LeaveCount,0) AS `LOP`,COALESCE(`Present`.`Count`,0) AS `PresentDays`,SalaryStructure.Basic,SalaryStructure.Allowance,SalaryStructure.HRA,SalaryStructure.TA,SalaryStructure.DA,SalaryStructure.Incentive,SalaryStructure.Basic+SalaryStructure.Allowance+SalaryStructure.HRA+SalaryStructure.TA+SalaryStructure.DA+SalaryStructure.Incentive-(COALESCE(`Leave`.LOP,0)*SalaryStructure.Basic/30) AS `GI`,SalaryStructure.PF,SalaryStructure.ESIC,SalaryStructure.ProTax,IF(COALESCE(`Advance`.`Balance`,0)<COALESCE(`Advance`.`EMI`,0),COALESCE(`Advance`.`Balance`,0),COALESCE(`Advance`.`EMI`,0)) AS `EMI`,SalaryStructure.PF+SalaryStructure.ESIC+IF(COALESCE(`Advance`.`Balance`,0)<COALESCE(`Advance`.`EMI`,0),COALESCE(`Advance`.`Balance`,0),COALESCE(`Advance`.`EMI`,0))+SalaryStructure.ProTax AS `GD`,((SalaryStructure.Basic+SalaryStructure.Allowance+SalaryStructure.HRA+SalaryStructure.TA+SalaryStructure.DA+SalaryStructure.Incentive-(COALESCE(`Leave`.LOP,0)*SalaryStructure.Basic/30))-(SalaryStructure.PF+SalaryStructure.ESIC+IF(COALESCE(`Advance`.`Balance`,0)<COALESCE(`Advance`.`EMI`,0),COALESCE(`Advance`.`Balance`,0),COALESCE(`Advance`.`EMI`,0))+SalaryStructure.ProTax)) AS `NET` FROM `SalaryStructure` LEFT JOIN (SELECT `Mobile`,Abs(SUM(IF(`LOP`>0,0,`LOP`))) AS `LOP`, Count(`Status`) As `Count` FROM `LeaveTracker` WHERE YEAR(`LeaveDate`)='$year' AND MONTH(`LeaveDate`)='$month' AND `Status`='Approved' GROUP BY `Mobile`) AS `Leave` ON `SalaryStructure`.Mobile = `Leave`.Mobile LEFT JOIN (SELECT `EMI`,`Mobile`,`Balance` FROM `AdvanceTracker` WHERE `Status`='Pending' AND MONTH(`StartDate`)<='$month' AND YEAR(`StartDate`)<='$year' AND `Balance`>0) AS `Advance` ON `SalaryStructure`.`Mobile` = `Advance`.`Mobile` LEFT JOIN (SELECT Count(`Location`) AS `Count`,`Mobile` FROM `Attendance` WHERE MONTH(`Date`)='$month' AND YEAR(`Date`)='$year' AND `Location`!='ABSENT' AND `Location`!='LEAVE' GROUP BY `Mobile`) AS `Present` ON `SalaryStructure`.`Mobile` = `Present`.`Mobile` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo`) AS `LeaveCount` ON `SalaryStructure`.`Mobile`=`LeaveCount`.`Mobile` WHERE `SalaryStructure`.`Mobile` IN (SELECT MObile FROM `EmployeeInfo` WHERE `Employer`='$emp')";

            
            // $stm = $this->conn->prepare($query);
            // if($stm->execute()===TRUE){
            //     return "Success";
            // }
            // else{
            //     return "Failed";
            // }


            //Fetch the Weekoff day from Settings
            $query = "SELECT `WeekOff`,`LeaveStructure`,`OverTime` FROM `Settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $weekoff=$row['WeekOff'];
            $leavestr=$row['LeaveStructure'];
            $OT=$row['OverTime'];
            
            
            //Calculate number of sundays
            $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
            $lastDay = date('Y-m-t', strtotime($firstDay));
            $totalDays = date('t', strtotime($firstDay));
        
            $sundayCount = 0;
            $currentDate = strtotime($firstDay);
        
            while (date('Y-m-d', $currentDate) <= $lastDay) {
                if (date('l', $currentDate) === $weekoff) {
                    $sundayCount++;
                }
                $currentDate = strtotime('+1 day', $currentDate);
            }
            
            //Calculate working days
            $query = "SELECT Count(`ID`) AS `COUNT` FROM `HolidayCalendar` WHERE YEAR(`Date`)='$year' AND MONTH(`Date`)='$month' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $holidays=$row['COUNT'];
            $workingDays = $totalDays-$holidays-$sundayCount;
            
            
            // echo "$emp";
            // echo " ";
            // echo "$totalDays";
            // echo " ";
            // echo "$sundayCount";
            // echo " ";
            // echo "$holidays";
            // echo " ";
            // echo "$workingDays";
            // echo " ";
            
            
            
            
            //Update Basic Details from salary structure
            $query ="INSERT INTO `PayRollTemplate` (`Employer`, `Month`, `Year`, `Name`, `Mobile`,`Days`,`WorkingDays`,`Basic`, `Allowance`, `HRA`, `TA`, `DA`, `Incentive`,`PF`, `ESIC`, `ProTax`) SELECT '$emp','$month','$year',`Name`, `Mobile`,'$totalDays','$workingDays',`Basic`, `Allowance`, `HRA`, `TA`, `DA`, `Incentive`,`PF`, `ESIC`, `ProTax` FROM `SalaryStructure` WHERE `Mobile` IN (SELECT `Mobile` FROM `UserInfo` WHERE `Mobile` in ($userValues) AND `Employer`='$emp') ORDER BY `Name`";
            $stm = $this->conn->prepare($query);
            $stm->execute();
                
                
            //Update LeaveDays and LOP
            $query="UPDATE `PayRollTemplate` AS `PRT` LEFT JOIN (SELECT SUM(`Days`) AS `Leave`,SUM(`LOP`) AS `LOP`,`Mobile` FROM `LeaveTracker` WHERE `Status`='Approved' AND MONTH(`LeaveDate`)='$month' AND YEAR(`LeaveDate`)='$year' GROUP BY `Mobile` ) AS `TLC` ON `PRT`.`Mobile`=`TLC`.`Mobile` SET `PRT`.`LeaveDays`=COALESCE(`TLC`.`Leave`,0),`PRT`.`LOP`=COALESCE(`TLC`.`LOP`,0) WHERE `PRT`.`Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            
                
            //Update Present days and Total LOP
            $query="UPDATE `PayRollTemplate` AS `PRT` LEFT JOIN (SELECT Count(`Status`) AS `Present`,`Mobile` FROM `Attendance` WHERE `Status`='Present' AND MONTH(`Date`)='$month' AND YEAR(`Date`)='$year' GROUP BY `Mobile` ) AS `TAC` ON `PRT`.`Mobile`=`TAC`.`Mobile` SET `PRT`.`PresentDays`=COALESCE(`TAC`.`Present`,0),`PRT`.`TotalLOP`=COALESCE(`PRT`.`LOP`+(`PRT`.`WorkingDays`-COALESCE(`TAC`.`Present`,0)-`PRT`.`LeaveDays`),0) WHERE `PRT`.`Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            //Update Overtime pay in incentive of Diamond Employees
            if($OT==='1'){
                $query="UPDATE `PayRollTemplate` SET `Incentive`=(`Incentive`+(`Basic`+`Allowance`+`HRA`+`TA`+`DA`+`Incentive`)/30*(5-`LeaveDays`)) WHERE `LeaveDays`<5 AND `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
            }
            
            //Update salary structure with LOP
            if($lop==="true"){
                $query="UPDATE `PayRollTemplate` SET `Basic`=ROUND(`Basic`-(`Basic`/30*`TotalLOP`),0),`Allowance`=ROUND(`Allowance`-(`Allowance`/30*`TotalLOP`),0),`HRA`=Round(`HRA`-(`HRA`/30*`TotalLOP`),0),`TA`=ROUND(`TA`-(`TA`/30*`TotalLOP`),0),`DA`=ROUND(`DA`-(`DA`/30*`TotalLOP`),0),`Incentive`=ROUND(`Incentive`-(`Incentive`/30*`TotalLOP`),0),`GrossIncome`=(`Basic`+`Allowance`+`HRA`+`TA`+`DA`+`Incentive`),`PF`=ROUND((`PF`/100*`GrossIncome`),0),`ESIC`=Round((`ESIC`/100*`GrossIncome`),0) WHERE `Employer`='$emp'";
                
            }
            else{
                $query="UPDATE `PayRollTemplate` SET `GrossIncome`=(`Basic`+`Allowance`+`HRA`+`TA`+`DA`+`Incentive`),`PF`=ROUND((`PF`/100*`GrossIncome`),0),`ESIC`=ROUND((`ESIC`/100*`GrossIncome`),0) WHERE `Employer`='$emp'";
            }
            $stm = $this->conn->prepare($query);
                $stm->execute();
            
            //Update Advance
            $query="UPDATE `PayRollTemplate` AS `PRT` LEFT JOIN (SELECT `Mobile`,`EMI`,`Balance` FROM `AdvanceTracker` WHERE `Mobile` IN (SELECT `Mobile` FROM `UserInfo` WHERE `Employer`='$emp') AND `Status`='Pending' AND MONTH(`StartDate`)<='$month' AND YEAR(`StartDate`)<='$year' AND `Balance`>0) AS `AD` ON `PRT`.`Mobile`=`AD`.`Mobile` SET `PRT`.`Advance`=COALESCE(if(`AD`.`EMI`>`AD`.`Balance`,`AD`.`Balance`,`AD`.`EMI`),0) WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            //Update Gross Deduction and NetPay
            $query = "UPDATE `PayRollTemplate` SET `GrossDeduction`=COALESCE((`PF`+`ESIC`+`ProTax`+`Advance`),0),`NetPay`=COALESCE(`GrossIncome`-`GrossDeduction`,0) WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            return "Success";
        }
        else{
            return "Failed";
        }
        
        
    }
    
    public function deleteTemplate(){
        
        if(!isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND Tocken='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            $query = "DELETE FROM `PayRollTemplate` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            return "Success";
            
        }
        else{
            return "Failed";
        }
        
    }
    
    
    
    public function getPayrollTemplate(){
        
        if(!isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND Tocken='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            $query = "SELECT * FROM `PayRollTemplate` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
            
        }
    }
    
    
    public function fetchPayRoll(){
        
        if(!isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        $device=trim($_POST['device']);
        $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND Tocken='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            $query = "SELECT * FROM `PayRollTracker` WHERE `Month`='$month' AND `Year`='$year' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
            
        }
    }
    
    
    
    public function addAdvance(){
        
        if(!isset($_POST['account']) || !isset($_POST['usermobile']) || !isset($_POST['amount']) || !isset($_POST['startdate'])){
            return;
        }

        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        // $username=trim($_POST['username']);
        if(isset($_POST['account'])){
            $account=trim($_POST['account']);
        }
        else{
            $account="NA";
        }

        if(isset($_POST['entrydate'])){
            $entrydate=trim($_POST['entrydate']);
        }
        else{
            $entrydate=$CurrDate;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $amount=trim($_POST['amount']);
        $emi=trim($_POST['emi']);
        $startdate=trim($_POST['startdate']);
        $emp=trim($_POST['emp']);



        $query = "SELECT `Name` FROM `UserInfo` WHERE Mobile='$usermobile' AND Employer='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $username = $row['Name'];
        
        $query = "SELECT * from `AdvanceTracker` WHERE `Mobile` = '$usermobile'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        // echo $rowCount;
        if($rowCount>0){
            $query = "UPDATE `AdvanceTracker` SET Amount= Amount+'$amount',Date='$CurrDate',EMI='$emi',StartDate='$startdate',Balance=Balance+'$amount',Status='Pending' WHERE `Mobile`='$usermobile'";
        }
        else{
            $query = "INSERT INTO `AdvanceTracker` (`Name`,`Mobile`,`Amount`,`Date`,`EMI`,`StartDate`,`Balance`,`Status`) VALUES ('$username','$usermobile','$amount','$CurrDate','$emi','$startdate','$amount','Pending')";
        }
        
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            
            // process_uploadBill($emp,$usermobile,$username,$type,$item,$shopid,$shop,$site,"","",0,$billno,$billamount,$billdate,$file,$fileavailable);
            $status = $this->process_uploadBill($emp,$usermobile,$username,"Salary Advance","",0,"","$account","","",0,"",$amount,$entrydate,"","false");
            
            $value = json_decode($status,true);
            // echo $value;
            if($value['Status']==="Success"){
                return "Advance added successfully";
            }
            else{
                return "Failed to update in Expense";
            }
            
        
        }
        else{
            return "Failed to add Advance";
        }
        
    }
    

    
    public function getAdvanceDetails(){
        
        if(!isset($_POST['usermobile'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        
        $query = "SELECT * FROM `AdvanceTracker` WHERE `Mobile` = '$usermobile'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount>0){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            // echo json_encode($row);
            return json_encode(array("Status"=>"Success","Mess"=>$row)); 
        }
        else{
           return json_encode(array("Status"=>"Failed")); 
        }
        
    }
    
    
    public function importPayRoll($req){
        
        
        
        $combinedArray= json_decode($req,true);
        
        $dataArray=$combinedArray['csvData'];
        $emp=$combinedArray['emp'];
        
        // echo $dataArray;
        $query = "DELETE FROM `PayRollTemplate` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        
        
        $i=0;
        foreach ($dataArray as $data) {
            // echo $data['Name'];
            $query = "INSERT INTO `PayRollTemplate` VALUES ('$data[Month]','$data[Year]','$data[Name]','$data[Mobile]','$data[Days]','$data[LeaveDays]','$data[LOP]','$data[PresentDays]','$data[Basic]','$data[Allowance]','$data[HRA]','$data[TA]','$data[DA]','$data[Incentive]','$data[GrossIncome]','$data[PF]','$data[ESIC]','$data[ProTax]','$data[Advance]','$data[GrossDeduction]','$data[NetPay]')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $i++;
            }
        }

        if(count($dataArray)===$i){
            return "File uploaded successfully";
        }
        else{
            return "Failed to upload file";
        }
        
        
    }
    
    
    public function rolloutPaySlip(){
        
        if(!isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND Tocken='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){

            
            //Reverse balance in advance tracker if any
            $query = "UPDATE `AdvanceTracker` AS at SET `Balance` = (SELECT `NewAdv`.`newBal` FROM (SELECT `AdvanceTracker`.`Mobile` AS `mob`,`AdvanceTracker`.`Balance`+`PayRollTracker`.`Advance` AS `newBal` FROM `AdvanceTracker` LEFT JOIN `PayRollTracker`  ON `AdvanceTracker`.`Mobile`=`PayRollTracker`.`Mobile` WHERE `PayRollTracker`.`Month` IN (SELECT `Month` FROM `PayRollTemplate`) AND `PayRollTracker`.`Year` IN (SELECT `Year` FROM `PayRollTemplate`) AND `PayRollTracker`.`Mobile` IN (SELECT `Mobile` FROM `PayRollTemplate`)) AS `NewAdv` WHERE at.Mobile=`NewAdv`.`mob`)";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            
            $query = "UPDATE `AdvanceTracker` SET `Status`='Pending' WHERE `Balance`>'0' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            // return;
            
            
            $query = "DELETE FROM `PayRollTracker` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `Month` IN (SELECT `Month` FROM `PayRollTemplate`) AND `Year` IN (SELECT `Year` FROM `PayRollTemplate`) AND `Mobile` IN (SELECT `Mobile` FROM `PayRollTemplate`)";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            
            $query = "INSERT INTO `PayRollTracker` SELECT * FROM `PayRollTemplate`";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                //Update advance tracker
                $query = "UPDATE `AdvanceTracker` AS at SET `Balance` = (SELECT `NewAdv`.`newBal` FROM (SELECT `AdvanceTracker`.`Mobile` AS `mob`,`AdvanceTracker`.`Balance`-`PayRollTemplate`.`Advance` AS `newBal` FROM `AdvanceTracker` LEFT JOIN `PayRollTemplate` ON `AdvanceTracker`.`Mobile`=`PayRollTemplate`.`Mobile` WHERE `AdvanceTracker`.`Status`='Pending') AS `NewAdv` WHERE at.Mobile=`NewAdv`.`mob`)";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                $query = "UPDATE `AdvanceTracker` SET `Status`='Completed' WHERE `Balance`='0' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                //Delete template
                $query = "DELETE FROM `PayRollTemplate` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                return "Rollout completed Successfully";
            }
            else{
                return "Failed to rollout";
            }
        }
    }
    
    
    
    public function getPaySlip(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['year']) || !isset($_POST['device']) || !isset($_POST['emp'])){
            return;
        }
        
        // $month=trim($_POST['month']);
        $year=trim($_POST['year']);
        $usermobile=trim($_POST['usermobile']);
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);

        
        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Tocken`='$device' AND `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            $query = "SELECT `PayRollTracker`.*,`EmployeeInfo`.`DOJ`,`EmployeeInfo`.`Department`,`EmployeeInfo`.`Position`,`PersonalData`.`BankName`,`PersonalData`.`AccNum`,`PersonalData`.`PAN`,`PersonalData`.`UAN`,`PersonalData`.`ESICNo` FROM `PayRollTracker` LEFT JOIN `EmployeeInfo` ON `PayRollTracker`.`Mobile`=`EmployeeInfo`.`Mobile` LEFT JOIN `PersonalData` ON `PayRollTracker`.`Mobile`=`PersonalData`.`Mobile` WHERE `PayRollTracker`.`Mobile`='$usermobile' AND  `PayRollTracker`.`Year`='$year' AND `PayRollTracker`.`Employer`='$emp'";
        }
        
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }
        
        
        
    }


    public function getEmployerDetails(){
        
        if(!isset($_POST['emp'])){
            return;
        }
        
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `employerinfo` WHERE `EmpShortname` = '$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        if($rowCount>0){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            // echo json_encode($row);
            return json_encode(array("Status"=>"Success","Mess"=>$row)); 
        }
        else{
           return json_encode(array("Status"=>"Failed")); 
        }
        
    }
    
    
    // public function getPaySlip(){
        
    //     if(!isset($_POST['month']) || !isset($_POST['year']) || !isset($_POST['device']) || !isset($_POST['emp'])){
    //         return;
    //     }
        
    //     $month=trim($_POST['month']);
    //     $year=trim($_POST['year']);
    //     $type=trim($_POST['type']);
    //     $device=trim($_POST['device']);
    //     $emp=trim($_POST['emp']);

        
    //     if($type==="ALL"){
    //         $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device' AND `Employer`='$emp'";
    //         $stm = $this->conn->prepare($query);
    //         $stm->execute();
    //         $chkCount = $stm->rowCount();
    //         // echo $chkCount;
    //         if($chkCount!=0){
    //             $query = "SELECT * FROM `PayRollTracker` WHERE `Month`='$month' AND `Year`='$year' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
    //         }
            
    //     }
    //     else{
    //         $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$type' AND `Tocken`='$device' AND `Employer`='$emp'";
    //         $stm = $this->conn->prepare($query);
    //         $stm->execute();
    //         $chkCount = $stm->rowCount();
    //         // echo $chkCount;
    //         if($chkCount!=0){
    //             $query = "SELECT `PayRollTracker`.*,`EmployeeInfo`.`DOJ`,`EmployeeInfo`.`Department`,`PersonalData`.`BankName`,`PersonalData`.`AccNum` FROM `PayRollTracker` LEFT JOIN `EmployeeInfo` ON `PayRollTracker`.`Mobile`=`EmployeeInfo`.`Mobile` LEFT JOIN `PersonalData` ON `PayRollTracker`.`Mobile`=`PersonalData`.`Mobile` WHERE `PayRollTracker`.`Mobile`='$type' AND `PayRollTracker`.`Month`='$month' AND `PayRollTracker`.`Year`='$year'";
    //         }
    //     }
        
    //     $stm = $this->conn->prepare($query);
    //     $stm->execute();
    //     $row = $stm->fetchall(PDO::FETCH_ASSOC);
    //     return json_encode($row);
        
        
    // }
    
    
    
    //========================================================================================================================================================================================= Holiday
    
    
    
    public function postHoliday(){
        
        if(!isset($_POST['emp']) || !isset($_POST['date']) || !isset($_POST['name']) || !isset($_POST['device'])){
            return;
        }
        
        $emp=trim($_POST['emp']);
        $date=trim($_POST['date']);
        $name=trim($_POST['name']);
        $device=trim($_POST['device']);
        
        
        
        $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount!=0){
            $query = "INSERT INTO `HolidayCalendar` (`Employer`, `Date`, `Name`) VALUES ('$emp','$date','$name')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Holiday added successfully";
            }
            else{
                return "Failed to add";
            }
        }
        else{
            return "Failed to authorize";
        }
    
    }
    
    public function getHoliday(){
        if(!isset($_POST['emp'])){
            return;
        }
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Ymd');
        
        $emp=trim($_POST['emp']);
        
        
        $query = "SELECT * FROM `HolidayCalendar` WHERE `Employer`='$emp' AND YEAR(`Date`)=YEAR($CurrDate) ORDER BY `Date` ASC";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }
        
    }
    
    
    public function deleteHoliday(){
        
        if(!isset($_POST['emp']) || !isset($_POST['id']) || !isset($_POST['device'])){
            return;
        }
        
        $emp=trim($_POST['emp']);
        $id=trim($_POST['id']);
        $device=trim($_POST['device']);
        
        
        
        $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount!=0){
            $query = "DELETE FROM `HolidayCalendar` WHERE `ID`='$id' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Holiday deleted successfully";
            }
            else{
                return "Failed to delete";
            }
        }
        else{
            return "Failed to authorize";
        }
    
    }
    
    public function getEvents(){
        
        if(!isset($_POST['emp'])){
            return;
        }
        
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
        $dob_flag=false;
        $ann_flag=false;
        
        
        $query = "SELECT `ID`,'Birthday' AS `Event`,`Name` FROM `PersonalData` WHERE MONTH(`DOB`)=MONTH('$CurrDate') AND DAY(`DOB`)=DAY('$CurrDate') AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp' AND `Status`='ACTIVE')";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $DOB = $stm->fetchall(PDO::FETCH_ASSOC);
        }
        else{
            $DOB=array();
        }
        
        $query = "SELECT `ID`,'Anniversary' AS `Event`,`Name` FROM `EmployeeInfo` WHERE `Status`='ACTIVE' AND MONTH(`DOJ`)=MONTH('$CurrDate') AND DAY(`DOJ`)=DAY('$CurrDate') AND `DOJ`!='$CurrDate' AND `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $DOJ = $stm->fetchall(PDO::FETCH_ASSOC);
        }
        else{
            $DOJ=array();
        }
        
        $result = array_merge($DOB, $DOJ);
        
        return json_encode($result);
        
        
        
        
    }
    
    
    //========================================================================================================================================================================================= Policy
    
    
    public function getPolicy(){
        
        if (isset($_POST['emp'])) {
            
            $emp=trim($_POST['emp']);
        
            $query = "SELECT * FROM `PolicyDocs` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row);
            }
            else{
                return "Failed";
            }
        }
    }
    
    public function uploadPolicy(){
        
        if (isset($_POST["title"]) && isset($_POST['emp']) && isset($_POST['device'])) {
            
            $title = $_POST["title"];
            $device = $_POST["device"];
            $emp = $_POST["emp"];
            
            date_default_timezone_set('Asia/Kolkata');
    
            $Time=date('His');
            $CurrDate=date('Ymd');
            $fileName = $title."-".$CurrDate.$Time.".pdf";
            
            $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $chkCount = $stm->rowCount();
            // echo $chkCount;
            if($chkCount!=0){
                $targetdir = "../../private_files/PolicyDocuments/$emp/"; // Directory where you want to store the uploaded files
                
                if (!is_dir($targetdir)) {
                    mkdir($targetdir, 0755, true);
                }
                
                $targetFile = $targetdir . $fileName;
                // $targetFile = $targetDir . basename($_FILES["file"]["name"]);
                // $fileName = basename($_FILES["file"]["name"]);

                // Check if file already exists
                if (file_exists($targetFile)) {
                    echo "File already exists.";
                } else {
                    // Move the uploaded file to the desired location
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
                        
                        
                        $query = "INSERT INTO `PolicyDocs` (`Title`,`Employer`,`FileName`) VALUES ('$title','$emp','$fileName')";
                        $stm = $this->conn->prepare($query);
                        $stm->execute();
                        
                        echo "File uploaded successfully.";
                    } else {
                        echo "Error uploading file.";
                    }
                }
            }
            else{
                return;
            }
            
            
            
        } else {
            return;
        }
        
    }
    
    public function deletePolicy(){
        
       if (!isset($_POST["emp"]) || !isset($_POST['id']) || !isset($_POST['device'])) {
            return;
        }  
        
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        $id=trim($_POST['id']);
        
        $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        // echo $chkCount;
        if($chkCount!=0){
            
            $query = "SELECT * FROM `PolicyDocs` WHERE `ID`='$id' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row=$stm->fetch(PDO::FETCH_ASSOC); 
            $pdfFile=$row['FileName'];
            if(strlen($pdfFile)>4){
                $file_pointer = "../../private_files/PolicyDocuments/$emp/$pdfFile";
                if(file_exists($file_pointer)){
                    if (!unlink($file_pointer)) {
                        return "$file_pointer cannot be deleted due to an error";
                    }
                }
                $query = "DELETE FROM `PolicyDocs` WHERE `ID`='$id'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return "Success";
                }
                else{
                    return "Failed";
                }
                
            }
            else{
                return "File identification Failed";
            }
        }
        else{
            return "Authentication Failed";
        }
        
    }
    
    //========================================================================================================================================================================================= Settings
    

    public function fetchSettingsList(){
        if (!isset($_POST["usermobile"]) || !isset($_POST['emp']) || !isset($_POST['selection'])) {
            return;
        }

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $selection=trim($_POST['selection']);
        

        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount=$stm->rowCount();

        if($rowCount!=0){

            if($selection==='Activity'){
                $selection='activitytype';
            }
            else if($selection==='Customer'){
                $selection='customertype';
            }
            else if($selection==='Vehicle'){
                $selection='vehicledata';
            }
            else if($selection==='Department'){
                $selection='departments';
            }
            else if($selection==='Position'){
                $selection='positions';
            }
            else if($selection==='Site Name'){
                $selection='projectsites';
            }
            else if($selection==='Bank'){
                $selection='accounts';
            }

            $query = "SELECT * FROM `$selection` WHERE `Employer`='$emp' ORDER BY `Type` asc";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row);
            }
            else{
                return "Failed";
            }

        }
        else{
            return "Failed";
        }

    }

    public function updateSettingsList(){

      
        if (!isset($_POST["usermobile"]) || !isset($_POST['emp']) || !isset($_POST['list'])) {
            return "Invalid";
        }
       
        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $list=trim($_POST['list']);
        $selection=trim($_POST['selection']);

        $bulkUsers = json_decode($list);
        $listValues = "'".implode("','", $bulkUsers)."'";

        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp' AND `Permission`!='User'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount=$stm->rowCount();

        if($rowCount!=0){

            if($selection==='Activity'){
                $selection='activitytype';
            }
            else if($selection==='Customer'){
                $selection='customertype';
            }
            else if($selection==='Vehicle'){
                $selection='vehicledata';
            }
            else if($selection==='Department'){
                $selection='departments';
            }
            else if($selection==='Position'){
                $selection='positions';
            }
            else if($selection==='Site Name'){
                $selection='projectsites';
            }
            else if($selection==='Bank'){
                $selection='accounts';
            }
            
            $query = "Delete FROM `$selection` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            $query = "INSERT INTO `$selection` (`Employer`,`Type`) VALUES ";
            
            foreach ($bulkUsers as $item){
                $value[] =  "('$emp','$item')";
            }
           
            $values_clause_string = implode(", ", $value);

            $query = $query . $values_clause_string ;

            // return $query;


            $stm = $this->conn->prepare($query);

            if($stm->execute()===TRUE){
                return "Success";
            }
            else{
                return "Failed";
            }
            
            
            // $stm = $this->conn->prepare($query);
            // $stm->execute();

        }


    }
    
    
    //========================================================================================================================================================================================= PAYMENT


    public function getSubscription(){

        if(!isset($_POST['usermobile']) || !isset($_POST['emp'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);

        date_default_timezone_set('Asia/Kolkata');
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        //Check user
        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' and `Permission`='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();

        $rowCount = $stm->rowCount();
        
        if($rowCount!=0){
            
            $query = "SELECT * FROM subscriptions WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }


    }

    public function updatePayment(){

        if(!isset($_POST['usermobile']) || !isset($_POST['emp']) || !isset($_POST['id'])){
            return;
        }

        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $amount=trim($_POST['amount']);
        $qty=trim($_POST['qty']);
        $id=trim($_POST['id']);
        $order=trim($_POST['order']);

        date_default_timezone_set('Asia/Kolkata');
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');

        $query = "SELECT * FROM subscriptions WHERE `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $row = $stm->fetch(PDO::FETCH_ASSOC);


        $rate = $row['Amount'];
        $fromDate= $row['Expiry'];
        $toDate= new DateTime($fromDate);
        $toDate->modify('+'.$qty.' months');
        $modifiedDate = $toDate->format('Y-m-d');


        
        $query = "INSERT INTO `payments` (`Employer`,`Amount`,`Unit`,`Total`,`FromDate`,`ToDate`,`TransactionID`,`OrderID`,`Date`,`Time`) VALUES ('$emp','$rate','$qty','$amount','$fromDate','$modifiedDate','$id','$order','$CurrDate','$Time')";
        $stm = $this->conn->prepare($query);
        if($stm->execute()){

            $query = "UPDATE subscriptions SET `Expiry`='$modifiedDate' WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            return "Success";
        }
        else{
            return "Failed";
        }


    }

    public function getOrderid(){

        $amount=trim($_POST['amount']);
        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile'  and `Employer`='$emp' and `Permission`='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount>0){
            $apiKey = 'rzp_live_aUnuQeSvJAEjzs'; // Replace with your Razorpay API Key
            $apiSecret = 'ZPCDYKMuoem1i8uC7TKJYdkP'; // Replace with your Razorpay API Secret

            // Razorpay API endpoint for creating orders
            $url = 'https://api.razorpay.com/v1/orders';

            // Order data
            $orderData = [
                // 'receipt'         => 'order_rcptid_11',
                'amount'          => $amount*100, // Amount in paise (50000 paise = 500 INR)
                'currency'        => 'INR',
                'payment_capture' => 1 // 1 for automatic capture, 0 for manual
            ];

            // Initialize cURL
            $ch = curl_init($url);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));

            // Execute cURL request
            $response = curl_exec($ch);

            // Check for errors
            if (curl_errno($ch)) {
                echo 'cURL error: ' . curl_error($ch);
                curl_close($ch);
                exit;
            }

            // Close cURL resource
            curl_close($ch);

            // Decode the JSON response
            $responseData = json_decode($response, true);

            // Check if response contains order ID
            if (isset($responseData['id'])) {
                $orderId = $responseData['id'];
                echo "$orderId";
            } else {
                echo "Failed";
            }
        }
        else{
            echo "Failed";
        }
    }

    public function validateSignature(){

        $razorpaySignature = $_POST['razorpay_signature']; // Signature sent by Razorpay
        $razorpayPaymentId = $_POST['razorpay_payment_id']; // Payment ID sent by Razorpay
        $razorpayOrderId = $_POST['razorpay_order_id']; // Order ID sent by Razorpay

        // Your Razorpay API Secret
        $apiSecret = 'ZPCDYKMuoem1i8uC7TKJYdkP'; // Replace with your actual Razorpay API Secret

        // Generate the expected signature using the API Secret and the provided parameters
        $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $apiSecret);

        // Validate the signature
        if (hash_equals($razorpaySignature, $generatedSignature)) {
            echo "Signature is valid";
            // Proceed with processing the payment
        } else {
            echo "Signature is invalid";
            // Handle the invalid signature scenario
        }
    }

    public function getPaymentList(){

        
        $usermobile=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile'  and `Employer`='$emp' and `Permission`='Admin'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount>0){
            
            $query = "SELECT * FROM payments WHERE `Employer`='$emp' ORDER BY ID DESC";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            return json_encode($row);
        }
        else{
            return "Failed";
        }

    }



    //========================================================================================================================================================================================= OTHER
    
    public function getZip(){
        
        if (!isset($_POST["usermobile"]) || !isset($_POST['emp'])) {
            return;
        }
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Ymd');

        $users=trim($_POST['usermobile']);
        $emp=trim($_POST['emp']);
        $fromDate=trim($_POST['fromDate']);
        $toDate=trim($_POST['toDate']);
        $item=trim($_POST['type']);



        $bulkUsers = json_decode($users);

        $userValues = "'".implode("','", $bulkUsers)."'";

        if($item=="Bill"){
            $query = "SELECT `Filename` FROM `ExpenseTracker` WHERE `Mobile` IN ($userValues) AND `Filename`!='NONE' AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount=$stm->rowCount();

            // Source and destination directories
            $sourceDir = '../../private_files/Bill_Images/'.$emp;
            $destinationDir = '../../private_files/TempFile';
            $zipFileName = $emp.'-'.$CurrDate.'-'.$Time.'.zip';
            $zipFilePath = $destinationDir . '/' . $zipFileName;
        }
        else if($item=="Collection"){
            $query = "SELECT `Filename` FROM `CollectionTracker` WHERE `Mobile` IN ($userValues) AND `Filename`!='NONE' AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount=$stm->rowCount();

            // Source and destination directories
            $sourceDir = '../../private_files/Collection/'.$emp;
            $destinationDir = '../../private_files/TempFile';
            $zipFileName = $emp.'-'.$CurrDate.'-'.$Time.'.zip';
            $zipFilePath = $destinationDir . '/' . $zipFileName;
        }

        
        
        
        if($rowCount>0){
            
            while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                
                $file[] = $row['Filename'];
            }
            
            

            // Create the destination directory if it doesn't exist
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0777, true);
            }

            // Initialize a new ZipArchive object
            $zip = new ZipArchive();
           
            
            // Open the archive file for writing
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                
                // Iterate over the array of file names
                foreach ($file as $fileName) {
                    $filePath = $sourceDir . '/' . $fileName;
                   
                    // Add the file to the ZIP archive
                    if (is_file($filePath)) {
                        $zip->addFile($filePath, $fileName);
                    } 
                }

                // Close the archive
                $zip->close();

                $byte_array = file_get_contents($zipFilePath);
                $data = base64_encode($byte_array);
                return "$data";

            } else {
                return "Failed";
            }

        }
        else{
            return "Failed";
        }

    }
    
    
    
    public function getPendingActions(){
        if (!isset($_POST["usermobile"]) || !isset($_POST['per']) || !isset($_POST['emp'])) {
            return;
        }    
        
        $usermobile=trim($_POST['usermobile']);
        $permission=trim($_POST['per']);
        $emp=trim($_POST['emp']);

        date_default_timezone_set('Asia/Kolkata');
        $CurrDate=date('Y-m-d');
        $pre_date = date('Y-m-d', strtotime("-60 days"));

        $pending=array();


        //Find Absent Days
        $query = "SELECT * FROM `Attendance` WHERE `Mobile`='$usermobile' AND `Status`='Absent' AND `Date`>'$pre_date'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $absentCount=$stm->rowCount();
        $absent = array("Type"=>"Absent Days","Count"=>"$absentCount");
        array_push($pending,$absent);

        if($permission!='User'){

            //Find regularization count
            if($permission=="Admin"){
                $query = "SELECT * FROM `Regularization` WHERE `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            }
            else{
                $query = "SELECT * FROM `Regularization` WHERE `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
            }
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $RCount = $stm->rowCount();
            if($RCount>0){
                $regularization = array("Type"=>"Regularization","Count"=>"$RCount");
                array_push($pending,$regularization);
            }

            //Find Leave Count
            if($permission=="Admin"){
                //YEAR(LeaveDate)=YEAR('$CurrDate') AND
                $query = "SELECT * FROM `LeaveTracker` WHERE (Status!='Approved' AND Status!='Rejected') AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            }
            else{
                $query = "SELECT * FROM `LeaveTracker` WHERE (Status!='Approved' AND Status!='Rejected') AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";   
            }
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $leaveCount = $stm->rowCount();
            if($leaveCount>0){
                $leave = array("Type"=>"Leave Requests","Count"=>"$leaveCount");
                array_push($pending,$leave);
            }
            
            
            if($permission=="Admin"){
                //Date BETWEEN '$pre_date' AND '$CurrDate' AND
                $query = "SELECT * FROM `ExpenseTracker` WHERE Status!='Approved' AND Status!='Rejected' AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            }
            else{
                $query = "SELECT * FROM `ExpenseTracker` WHERE Status!='Approved' AND Status!='Rejected' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
            }
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $expenseCount = $stm->rowCount();
            if($expenseCount>0){
                $expense = array("Type"=>"Expense Claims","Count"=>"$expenseCount");
                array_push($pending,$expense);
            }
            
        }

        // $tot = $absentCount+$RCount+$leaveCount+$expenseCount;
        // if($tot==0){
        //     $tot = array("Type"=>"Noname","Count"=>"Noname");
        //     return json_encode($tot);
        // }
        // else{
        //     return json_encode($absent);
        // }

        // $totCount = $absentCount+$RCount+$leaveCount+$expenseCount;
        // $tot = array("Type"=>"Total","Count"=>"$totCount");
        // array_push($pending,$tot);

        return json_encode($pending);
        
        

    }
    
    public function getDashboardSummary(){
        
        if (!isset($_POST["usermobile"]) || !isset($_POST['permission']) || !isset($_POST['emp'])) {
            return;
        }    
        
        $usermobile=trim($_POST['usermobile']);
        $permission=trim($_POST['permission']);
        $emp=trim($_POST['emp']);

        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        $pre_date = date('Y-m-d', strtotime("-20 days"));
        
        if($permission=="Admin"){
            $query = "SELECT * FROM `Regularization` WHERE `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        }
        else{
            $query = "SELECT * FROM `Regularization` WHERE `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
        }
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $regularizationCount = $stm->rowCount();
        
        
        if($permission=="Admin"){
            $query = "SELECT * FROM `Activity` WHERE `ActivityDate`= '$CurrDate' AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        }
        else{
            $query = "SELECT * FROM `Activity` WHERE `ActivityDate`= '$CurrDate' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
        }
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $activityCount = $stm->rowCount();
        
        if($permission=="Admin"){
            //YEAR(LeaveDate)=YEAR('$CurrDate') AND
            $query = "SELECT * FROM `LeaveTracker` WHERE (Status!='Approved' AND Status!='Rejected') AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        }
        else{
            $query = "SELECT * FROM `LeaveTracker` WHERE (Status!='Approved' AND Status!='Rejected') AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";   
        }
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $leaveCount = $stm->rowCount();
        
        
        if($permission=="Admin"){
            //Date BETWEEN '$pre_date' AND '$CurrDate' AND
            $query = "SELECT * FROM `ExpenseTracker` WHERE Status!='Approved' AND Status!='Rejected' AND `Mobile` IN (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        }
        else{
            $query = "SELECT * FROM `ExpenseTracker` WHERE Status!='Approved' AND Status!='Rejected' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile'))";
        }
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $expenseCount = $stm->rowCount();
        
        return json_encode(array("Regularization"=>"$regularizationCount","Activity"=>"$activityCount","Leave"=>"$leaveCount","Expense"=>"$expenseCount"));
        
        
    }
    
    
    
    //Downloding trackers
     public function get_tracker(){
        
        $device=trim($_POST['device']);
        $item=trim($_POST['item']);
        $fromDate=trim($_POST['fromDate']);
        $toDate=trim($_POST['toDate']);
        $emp=trim($_POST['emp']);
        $users=trim($_POST['users']);

        $bulkUsers = json_decode($users);
        $userValues = "'".implode("','", $bulkUsers)."'";
            
        
        $query = "SELECT * FROM `UserInfo` WHERE `Tocken` = '$device' AND Permission = 'Admin' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            
            
            if($item==='Attendance'){
                $query = "SELECT * FROM `Attendance` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp') AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            else if($item==='Expense'){
                // $query = "SELECT ExpenseTracker.Mobile,EmployeeInfo.Name,ExpenseTracker.Site,ExpenseTracker.FromLoc,ExpenseTracker.ToLoc,ExpenseTracker.KM,ExpenseTracker.Date,ExpenseTracker.Type,ExpenseTracker.BillNo,ExpenseTracker.Amount,ExpenseTracker.Filename,ExpenseTracker.Status FROM ExpenseTracker INNER JOIN EmployeeInfo WHERE ExpenseTracker.Mobile = EmployeeInfo.Mobile AND ExpenseTracker.Date BETWEEN '$fromDate' AND '$toDate' AND ExpenseTracker.`Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                // $query = "SELECT * FROM ExpenseTracker WHERE Date BETWEEN '$fromDate' AND '$toDate' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                $query = "SELECT ExpenseTracker.*,BT.ShopName,BT.District AS ShopDist,BT.Phone AS ShopPhone,BT.GST AS ShopGST FROM ExpenseTracker LEFT JOIN (SELECT * FROM `BillerTracker` WHERE `Employer`='$emp') AS `BT` ON `ExpenseTracker`.`Site`=`BT`.`ShopID` AND `ExpenseTracker`.`Type`='Purchase' WHERE `ExpenseTracker`.Date BETWEEN '$fromDate' AND '$toDate' AND `ExpenseTracker`.`Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp')";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            else if($item==='Activity'){
                
                $query = "SELECT * FROM `Activity` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp') AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
                
            }
            else if($item==='Material'){
                
                $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Item`='Material' AND `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp') AND `collectiontracker`.`Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
                
            }
            else if($item==='Cash'){
                
                $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Item`!='Material' AND `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp') AND `collectiontracker`.`Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
                
            }
            else if($item==='Locations'){
                
                $query = "SELECT * FROM `billertracker` WHERE `CreateMobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Mobile` IN ($userValues) AND `Employer`='$emp') AND `CreateDate` BETWEEN '$fromDate' AND '$toDate' ORDER BY `CreateDate`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
                
            }
        }
    }
    
    
    public function getAccounts(){
        
        if (!isset($_POST["device"]) || !isset($_POST["emp"])) {
            return;
        }
        
        $device=trim($_POST['device']);
        $emp=trim($_POST['emp']);
        
        $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $chkCount = $stm->rowCount();
        if($chkCount!=0){
            $query = "SELECT `AccName`,`Type` FROM `Accounts` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
               $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
                return json_encode($row); 
            }
            else{
                return "Failed";
            }
            
        }
        else{
            return "Failed";
        }
        
    }

    public function getSettings(){
        if (!isset($_POST["param"]) || !isset($_POST["emp"])) {
            return;
        }

        $param=trim($_POST["param"]);
        $emp=trim($_POST["emp"]);

        $query = "SELECT `$param` FROM `Settings` WHERE `Employer`='$emp'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            return $row[$param]; 
        }
        else{
            return "Failed";
        }

    }
    
    
    //=========================================================================================================================================================================================CRON
    
    
    public function cron_Absent(){
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        // $pre_date = date('Y-m-d', strtotime("-1 days"));
        
        // echo $pre_date;
        
        $day = date('l',strtotime("$CurrDate"));
        
        if($day=='Sunday'){
            echo "Sunday";
        }
        else{
            
            // $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Location`) VALUES SELECT `Mobile`,`Name`,'0.00','0.00','$pre_date','00:00:00','0.00','0.00','00:00:00','Absent' FROM `EmployeeInfo` WHERE `Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$pre_date' )";
            $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$CurrDate','00:00:00','0.00','0.00','00:00:00',IF(`LeaveTracker`.`Status`='Approved','Leave','Absent'),IF(`LeaveTracker`.`Status`='Approved','Leave','Absent') FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$CurrDate' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `EmployeeInfo`.`Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$CurrDate')";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            return "Done -".$CurrDate."-".$Time;
        }
        
    }
    
    
    
}

?>
