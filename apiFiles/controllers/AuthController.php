<?php

    require_once '/var/private_files/controllers/EmailController.php';

    require '/var/myhrFiles/php-jwt/vendor/autoload.php';
    use \Firebase\JWT\JWT;
    use \Firebase\JWT\Key;

    class AuthController {
        
        private $conn;
    
        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function login_fun($data){

            // $data = json_decode($data,true);
            $secretKey = 'jilaritechnologies-jwt-tocken';
            
            
            
            if(!isset($data['usermobile']) || !isset($data['userpass']) || !isset($data['app'])){
                return;
            }
           
            
            $usermobile=trim($data['usermobile']);
            $userpass=trim($data['userpass']);
            $app=trim($data['app']);
            // $emp=trim($data['emp']);
            
            if($userpass==='DEACT'){
                return;
            }
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
            
            
            // if($appVersion!=$app){
            //     return json_encode(array("Data"=>"App version check failed","Status"=>"Failed"));
            // }
            // else{



                
                $query = "SELECT `UserInfo`.`ID`,`Userinfo`.`Pin`,`settings`.`Status`,`UserInfo`.`Permission`,`UserInfo`.`Employer` FROM `Userinfo` LEFT JOIN `settings` ON `UserInfo`.`Employer`=`settings`.`Employer` Where `Mobile`='$usermobile' ORDER BY `UserInfo`.`ID` DESC LIMIT 1";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                if($rowCount!=0){
                    
                    $row = $stm->fetch(PDO::FETCH_ASSOC);
                    
                    if($row['Status']==='0' && $row['Permission']!='Admin'){
                        return json_encode(array("Data"=>"Account Expired","Status"=>"Failed"));
                    }
                    else{

                    
                        if($row['Pin']===$userpass || password_verify($userpass,$row['Pin'])){
        
                            $query = "UPDATE `UserInfo` SET `appversion`='$app', `lastlogin`= NOW() WHERE `Mobile`='$usermobile' AND `Employer`='$row[Employer]'";
                            $stm = $this->conn->prepare($query);
                            $stm->execute();
                            
                            $query ="SELECT `UserInfo`.Name,`UserInfo`.Mobile,`UserInfo`.Email,`UserInfo`.EmployeeID,`UserInfo`.Employer,`UserInfo`.Permission,`UserInfo`.resetpassword,
                                    `EmployeeInfo`.Department,`EmployeeInfo`.Position,`EmployeeInfo`.Manager,`EmployeeInfo`.ManagerID,`EmployeeInfo`.DOJ,`UserInfo`.Tocken,
                                    `EmployeeInfo`.ImageFile,`EmployeeInfo`.LeaveCount,IF('$row[Status]'='0' AND '$row[Permission]'='Admin','LOCKED',`EmployeeInfo`.`Status`) AS `Status` FROM `UserInfo` 
                                    LEFT JOIN `EmployeeInfo` ON `UserInfo`.Mobile=`EmployeeInfo`.Mobile WHERE `UserInfo`.Mobile = '$usermobile' AND `UserInfo`.`Employer`='$row[Employer]' AND `EmployeeInfo`.`Status`!='INACTIVE'";
                        
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
                }
                else{
                    return json_encode(array("Data"=>"Credentials Failed","Status"=>"Failed"));
                }
    
                
                       
                
            // }
            
            // $conn->close();
    
        }


        public function register($data){

            // return "New Registrations are on hold";

            if(!isset($data['usermobile']) || !isset($data['username']) || !isset($data['useremail'])){
                return;
            }
    
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $useremail=trim($data['useremail']);
            $id=trim($data['id']);
            $employer=trim($data['employer']);
            $l1=trim($data['l1']);
            $l2=trim($data['l2']);
            
            if (!filter_var($useremail, FILTER_VALIDATE_EMAIL)) {
                return "Invalid MailID";
            }

            // Remove spaces (both leading, trailing, and in-between spaces)
            $trimmedString = str_replace(' ', '', $employer);
    
            // Calculate the length of the modified string
            $length = strlen($trimmedString);
            if($length>10){
                $short1 = substr($trimmedString, 0, 10);
            }
            else{
                $short1 = $trimmedString;
            }
    
            $short="";
            while(1){
                $empid = rand(10, 99);
                $short=$short1.$empid;
                $query = "SELECT * FROM `Settings` Where `Employer`='$short'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                if($rowCount==0){
                    break;
                }
            }
    
            $query = "SELECT * FROM `Userinfo` Where (`Mobile`='$usermobile' OR `Email`='$useremail') AND `Employer`='$short'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                return "Mobile/Email already registered";
            }
    
            $pin = rand(100000, 999999);
            $hpin = password_hash($pin, PASSWORD_DEFAULT);
    
            
    
            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
    
            $toDate= new DateTime($CurrDate);
            $toDate->modify('+12 months');
            $modifiedDate = $toDate->format('Y-m-d');
    
            $query = "INSERT INTO `EmployerInfo` (`EmpName`,`EmpShortname`,`AddressL1`,`AddressL2`) VALUES ('$employer','$short','$l1','$l2');
                        INSERT INTO `UserInfo` (`Name`,`Mobile`,`Email`,`EmployeeID`,`Employer`,`Pin`,`Permission`,`resetpassword`,`appversion`) VALUES ('$username','$usermobile','$useremail','$id','$short','$hpin','Admin','TRUE','V1');
                        INSERT INTO `EmployeeInfo` (`Name`,`Mobile`,`EmployeeID`,`Employer`,`Manager`,`ManagerID`,`Status`) VALUES ('$username','$usermobile','$id','$short','$username','$id','ACTIVE');
                        INSERT INTO `PersonalData` (`Name`,`Mobile`,`Employer`) VALUES ('$username','$usermobile','$employer');
                        INSERT INTO `settings` (`Employer`,`Users`) VALUES ('$short','10');
                        INSERT INTO `subscriptions` (`Employer`,`Amount`,`Expiry`,`EmpCount`) VALUES ('$short','0','$modifiedDate','10');
                        INSERT INTO `Accounts` (`Employer`,`Type`) VALUES ('$short','Cash'),('$short','UPI');
                        INSERT INTO `Departments` (`Employer`,`Type`) VALUES ('$short','Administration'),('$short','Finance'),('$short','Marketing'),('$short','HR'),('$short','Sales');
                        INSERT INTO `Positions` (`Employer`,`Type`) VALUES ('$short','Executive'),('$short','MD'),('$short','Lead');";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $controller = new EmailController();
                $status = $controller->sendEmail($username,$useremail,$pin,"new");
    
                return "Success";
            }
            else{
                return "Failed";
            }
        }


        // public function markAbsent(){
        
        //     date_default_timezone_set('Asia/Kolkata');
        
        //     $Time=date('H:i:s');
        //     $CurrDate=date('Y-m-d');


        //     if($Time<'23:50:00'){
        //         return;
        //     }
        //     // $CurrDate='2024-06-12';
    
        //     // $pre_date = date('Y-m-d', strtotime("-1 days"));
        //     $next_date = date('Y-m-d', strtotime("+1 days"));
            
        //     // echo $pre_date;
            
        //     $day = date('l',strtotime("$CurrDate"));
    
        //     $flag=0;
    
    
    
        //     //Mark offday for next day as per holiday table
        //     $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `Mobile`,`Name`,'0.00','0.00','$next_date','00:00:00','0.00','0.00','00:00:00','Holiday','Holiday' FROM `EmployeeInfo` WHERE `Employer` in (SEELCT Employer FROM `holidaycalendar` WHERE `Date`='$next_date')";
        //     $stm = $this->conn->prepare($query);
        //     if($stm->execute()===TRUE){
        //         $flag=$flag+1;
        //     } 
    
        //     //Mark Absent for current Day for those didnt apply
        //     // $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Location`) VALUES SELECT `Mobile`,`Name`,'0.00','0.00','$pre_date','00:00:00','0.00','0.00','00:00:00','Absent' FROM `EmployeeInfo` WHERE `Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$pre_date' )";
        //     $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$CurrDate','00:00:00','0.00','0.00','00:00:00',IF(`LeaveTracker`.`Status`='Approved','Leave','Absent'),IF(`LeaveTracker`.`Status`='Approved','Leave','Absent') FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$CurrDate' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `EmployeeInfo`.`Employer` IN (SELECT `Employer` FROM `Settings` WHERE FIND_IN_SET('$day', `WeekOff`) = 0) AND `EmployeeInfo`.`Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$CurrDate')";
        //     $stm = $this->conn->prepare($query);
        //     if($stm->execute()===TRUE){
        //         $flag=$flag+1;
        //     }    
    
        //     //Mark Leave for those who applied leave
        //     $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$next_date','00:00:00','0.00','0.00','00:00:00','Leave','Leave' FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$next_date' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `LeaveTracker`.`Status`='Approved'";
        //     $stm = $this->conn->prepare($query);
        //     if($stm->execute()===TRUE){
        //         $flag=$flag+1;
        //     }
            
        //     //Mark Deactiviation of account once expired
        //     $query = "UPDATE `settings` SET `Status`=0 WHERE `Status`!=0 AND `Employer` IN (SELECT `Employer` FROM `subscriptions` WHERE `Expiry` < DATE_SUB(CURDATE(), INTERVAL 5 DAY))";
        //     $stm = $this->conn->prepare($query);
        //     if($stm->execute()===TRUE){
        //         $flag=$flag+1;
        //     }
    
    
        //     if($flag>0){
        //     return "Done -".$flag."-".$CurrDate."-".$Time;
        //     }
        //         else{
        //     return "Error -".$CurrDate."-".$Time;
        //     }
    
            
        // }


        //  public function sendAlert(){

        //     //Send Notification before expiry
        //     $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
        //     LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
        //     LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Mobile`='9747050500'";
            

        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $rowCount = $stm->rowCount();
        //     $rows = $stm->fetchall(PDO::FETCH_ASSOC);
        //     if($rowCount>0){

                

        //         $controller2 = new EmailController();
        //         foreach ($rows as $row) {
        //             if (!empty($row['Email'])) {
        //                 $controller2->sendExpiry($row['Email'],$row['Name'],'Alert');
        //             }
        //         }
                

        //         $controller = new FCMController($this->conn);
        //         foreach ($rows as $row) {
        //             $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account is nearing expiry. Renew now to avoid service interruption.');
        //         }

        //         $emails = [];
                

                

        //         // print_r($emails);
        //         // error_log($emails[0]);

                

        //         echo "Notification send";

        //     }

        //  }



        // public function sendAlertTest(){
    
            
        //     //Send Notification before expiry - before 5 and 1 day
        //     $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
        //     LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
        //     LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND (`subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 5 DAY) OR `subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 1 DAY))";
            

        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $rowCount = $stm->rowCount();
        //     $rows = $stm->fetchall(PDO::FETCH_ASSOC);
        //     if($rowCount>0){


        //         $controller2 = new EmailController();
        //         foreach ($rows as $row) {
        //             if (!empty($row['Email'])) {
        //                 $controller2->sendExpiry($row['Email'],$row['Name'],'Alert');
        //             }
        //         }

        //         $controller = new FCMController($this->conn);
        //         foreach ($rows as $row) {
        //             $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account is nearing expiry. Renew now to avoid service interruption.');
        //         }

        //         echo "Notification send";

        //     }




        //     //Send Warning before expiry - after 1 and 3 days
        //     $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
        //     LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
        //     LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND (`subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 1 DAY) OR `subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 3 DAY))";
            

        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $rowCount = $stm->rowCount();
        //     $rows = $stm->fetchall(PDO::FETCH_ASSOC);
        //     if($rowCount>0){

        //         $controller2 = new EmailController();
        //         foreach ($rows as $row) {
        //             if (!empty($row['Email'])) {
        //                 $controller2->sendExpiry($row['Email'],$row['Name'],'Warning');
        //             }
        //         }

        //         $controller = new FCMController($this->conn);
        //         foreach ($rows as $row) {
        //             $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account has been expired. Renew now to avoid service interruption.');
        //         }

        //         echo "Notification send";

        //     }



        //     //Send Locking notification - on 8th day

        //     $query = "UPDATE `settings` SET `Status`=0 WHERE `Employer` IN (SELECT `Employer` FROM `subscriptions` WHERE `Expiry` = DATE_ADD(CURDATE(), INTERVAL 8 DAY))";
        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();

        //     $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
        //     LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
        //     LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND `subscriptions`.`Expiry` = DATE_ADD(CURDATE(), INTERVAL 8 DAY)";
            

        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $rowCount = $stm->rowCount();
        //     $rows = $stm->fetchall(PDO::FETCH_ASSOC);
        //     if($rowCount>0){

        //         $controller2 = new EmailController();
        //         foreach ($rows as $row) {
        //             if (!empty($row['Email'])) {
        //                 $controller2->sendExpiry($row['Email'],$row['Name'],'Lock');
        //             }
        //         }

        //         $controller = new FCMController($this->conn);
        //         foreach ($rows as $row) {
        //             $controller->send_fcm_message($row['fcmtocken'],'Subscription Expired','Your account has been locked. Renew now to gain full access.');
        //         }

        //         echo "Notification send";

        //     }

        // }





        public function payment($data){
    
           
            // webhook.php
            $secret = "70bb4fa90a3b9a375946ef1b8fec"; // Set in Razorpay dashboard
            // $secret = "test_secret"; // Set in Razorpay dashboard
            $input = file_get_contents('php://input');
            $headers = getallheaders();

            date_default_timezone_set('Asia/Kolkata');
            $CurrDate=date('Y-m-d');
    
            // error_log($headers);
            // Validate Razorpay signature
    
            if (!isset($headers['X-Razorpay-Signature'])) {
                http_response_code(400);
                exit("Invalid request");
            }
    
            $sig = $headers['X-Razorpay-Signature'];
            $expectedSig = hash_hmac('sha256', $input, $secret);
    
            if (!hash_equals($expectedSig, $sig)) {
                http_response_code(400);
                exit("Signature mismatch");
            }
    
            // Decode JSON data
            $data = json_decode($input, true);
            $paymentId = $data['payload']['payment']['entity']['id'];
            $orderId = $data['payload']['payment']['entity']['order_id'];
            $status = $data['payload']['payment']['entity']['status'];
            //(`Employer`,`Amount`,`Unit`,`Total`,`FromDate`,`ToDate`,`TransactionID`,`OrderID`,`Date`,`Time`) VALUES ('$emp','$rate','$qty','$amount','$fromDate','$modifiedDate','Pending','$order','$CurrDate','$Time')
            
            $query = "SELECT * FROM `payments` WHERE `OrderID`='$orderId'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $toDate= $row['ToDate'];
            $emp = $row['Employer'];
            
            
            $query = "UPDATE `payments` SET `TransactionID`='$paymentId',`Status`='$status' WHERE `OrderID`='$orderId';
                      UPDATE subscriptions SET `Expiry`='$toDate' WHERE `Employer`='$emp';";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            if($toDate > $CurrDate){
                $query = "UPDATE `settings` SET `Status`='1' WHERE `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
            }
            
 
            http_response_code(200);
            echo "Webhook processed successfully";
    
    
    
        }




    }
?>
