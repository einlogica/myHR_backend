<?php

    require_once '/var/private_files/controllers/EmailController.php';

    class UserController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function register($data){

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
    
            
            // $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$employer'";
            // $stm = $this->conn->prepare($qry);
            // $stm->execute();
            // $tz = $stm->fetch(PDO::FETCH_ASSOC);
            // $timezone = $tz['TimeZone'];
        
            // date_default_timezone_set($timezone);

            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
    
            $toDate= new DateTime($CurrDate);
            $toDate->modify('+12 months');
            $modifiedDate = $toDate->format('Y-m-d');
    
            $query = "INSERT INTO `EmployerInfo` (`EmpName`,`EmpShortname`,`AddressL1`,`AddressL2`) VALUES ('$employer','$short','$l1','$l2');
                        INSERT INTO `UserInfo` (`Name`,`Mobile`,`Email`,`EmployeeID`,`Employer`,`Pin`,`Permission`,`resetpassword`,`appversion`) VALUES ('$username','$usermobile','$useremail','$id','$short','$hpin','Admin','TRUE','V1');
                        INSERT INTO `EmployeeInfo` (`Name`,`Mobile`,`EmployeeID`,`Employer`,`Manager`,`ManagerID`,`Status`) VALUES ('$username','$usermobile','$id','$short','$username','$id','ACTIVE');
                        INSERT INTO `PersonalData` (`Name`,`Mobile`,`Employer`) VALUES ('$username','$usermobile','$short');
                        INSERT INTO `settings` (`Employer`,`Users`) VALUES ('$short','10');
                        INSERT INTO `subscriptions` (`Employer`,`Amount`,`Expiry`,`EmpCount`) VALUES ('$short','0','$modifiedDate','10');
                        INSERT INTO `Accounts` (`Employer`,`Type`) VALUES ('$short','Cash'),('$short','UPI');
                        INSERT INTO `Departments` (`Employer`,`Type`) VALUES ('$short','Administration'),('$short','Finance'),('$short','Marketing'),('$short','HR'),('$short','Sales');
                        INSERT INTO `Positions` (`Employer`,`Type`) VALUES ('$short','Executive'),('$short','MD'),('$short','Lead');";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $controller = New EmailController();
                $status = $controller->sendEmail($username,$useremail,$pin,"new");
    
                return "Success";
            }
            else{
                return "Failed";
            }
        }



        public function upload_image($data){
        
            if(!isset($data['usermobile']) || !isset($data['file'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $file=$data['file'];
            $emp=$data['emp'];
            
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
    
    
        public function upload_logo($data){
            
            if(!isset($data['usermobile']) || !isset($data['file'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $file=$data['file'];
            $emp=$data['emp'];
            
            $target_dir ="../../private_files/Icons/";
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $filename = $emp.".png";
            $target_file = $target_dir . $filename;
            $data = base64_decode($file);  
            
            if (file_put_contents($target_file, $data)) {
                
                // $query = "UPDATE `ExpenseUsers` SET `ImageFile`='$filename' WHERE `Mobile`='$usermobile' ";
                // $query = "UPDATE `EmployeeInfo` SET `ImageFile`='$filename' WHERE `Mobile`='$usermobile' ";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                return "Successfully uploaded";
                
                
            }
            else{
                return "Failed to upload image";
            }
            
        }
        
        
        
        public function get_userdetails($data){
            
            if(!isset($data['filter']) || !isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $filter=trim($data['filter']);
            $mobile=trim($data['mobile']);
            $emp=trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('His');
            $CurrDate=date('Y-m-d');
            
            
            if($filter==='ALL'){
                // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,Permission,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers`";
                $query = "SELECT `UserInfo`.`ID`,`EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp'";
                
            }
            else if($filter==='MANAGERLIST'){
                // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers` WHERE `Permission` ='Manager' or `Permission` ='Admin' ";
                $query = "SELECT `UserInfo`.`ID`,`EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp' AND (`UserInfo`.`Permission` ='Manager' OR `UserInfo`.`Permission` ='Admin') ";
            }
            else if($filter==='MANAGER'){
                // $query = "SELECT ExpenseUsers.Mobile,ExpenseUsers.Name,ExpenseUsers.EmployeeID,ExpenseUsers.Department,ExpenseUsers.Position,ExpenseUsers.Manager,ExpenseUsers.ManagerID,ExpenseUsers.Permission,Att.Location,ExpenseUsers.LeaveCount,ExpenseUsers.LeaveBalance,ExpenseUsers.ImageFile FROM `ExpenseUsers` LEFT JOIN (SELECT Location,Mobile FROM `Attendance` WHERE `Date`='$CurrDate') as `Att` ON ExpenseUsers.Mobile=Att.Mobile WHERE ExpenseUsers.ManagerID in (SELECT EmployeeID FROM `ExpenseUsers` Where `Mobile`='$mobile')";
                $query = "SELECT ``UserInfo`.`ID`,EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email`,Att.Location,Att.Status FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` LEFT JOIN (SELECT Status,Location,Mobile FROM `Attendance` WHERE `Date`='$CurrDate') as `Att` ON `EmployeeInfo`.`Mobile`=Att.`Mobile` WHERE `EmployeeInfo`.`ManagerID` in (SELECT EmployeeID FROM `EmployeeInfo` Where `Mobile`='$mobile') AND `EmployeeInfo`.`Employer`='$emp'";
            }
            else if($filter === 'ONE'){
                // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,Permission,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers` WHERE `Mobile` ='$mobile' ";
                $query = "SELECT `UserInfo`.`ID`,`EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Mobile` ='$mobile' AND `EmployeeInfo`.`Employer`='$emp'";
            }
            else if($filter === 'SUPER'){
                // $query = "SELECT Mobile,Name,EmployeeID,Department,Position,Manager,ManagerID,Permission,LeaveCount,LeaveBalance,ImageFile FROM `ExpenseUsers` WHERE `Mobile` ='$mobile' ";
                $query = "SELECT `UserInfo`.`ID`,`UserInfo`.`Employer`,`EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN 
                            `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE 
                            `EmployeeInfo`.`Status`='ACTIVE' AND `UserInfo`.`Permission`='Admin' ORDER BY  `EmployeeInfo`.`Employer`,`EmployeeInfo`.`Name`";
    
                // $query = "SELECT `UserInfo`.`Employer`,`EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN 
                //             `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `UserInfo`.`Permission`='Admin' AND 
                //             `EmployeeInfo`.`Status`='ACTIVE' AND `UserInfo`.`Mobile` in (SELECT MIN(`EmployeeInfo`.`Mobile`) FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON 
                //             `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `UserInfo`.`Permission`='Admin' GROUP BY `EmployeeInfo`.`Employer`)";            
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
        
        public function getProfile($data){
            
            if(!isset($data['device']) || !isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $device=trim($data['device']);
            $mobile=trim($data['mobile']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE `Tocken`='$device' AND (`Employer`='$emp' OR `Permission`='Superuser')";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
    
            if($rowCount!=0){
                $query = "SELECT `EmployeeInfo`.*,`UserInfo`.`Permission`,`UserInfo`.`Email` FROM `EmployeeInfo` LEFT JOIN `UserInfo` ON `EmployeeInfo`.`Mobile`=`UserInfo`.`Mobile` AND `EmployeeInfo`.`Employer`=`UserInfo`.`Employer` WHERE `EmployeeInfo`.`Mobile` ='$mobile' AND (`EmployeeInfo`.`Employer`='$emp' OR `EmployeeInfo`.`Employer`='Jilari')";
                
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
        
        public function getReportees($data){
            
           
            
            if(!isset($data['filter']) || !isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $filter=trim($data['filter']);
            $mobile=trim($data['mobile']);
            $emp=trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('His');
            $CurrDate=date('Y-m-d');
            
            if($filter==='ALL'){
                
                if(isset($data['date'])){
                    $CurrDate=$data['date'];
                }
            
                $query = "SELECT `EmployeeInfo`.`Name`,`EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Position`,`Att`.`Status` AS AttStatus,`Att`.`Location`,`EmployeeInfo`.`Status` FROM `EmployeeInfo` LEFT JOIN (SELECT `Mobile`,`Status`,`Location` FROM `Attendance` WHERE `Date`='$CurrDate') AS Att ON `EmployeeInfo`.`Mobile`=`Att`.`Mobile` WHERE `EmployeeInfo`.`Employer`='$emp' ORDER BY `EmployeeInfo`.`Status` ASC,`Att`.`Status` DESC,`EmployeeInfo`.`Name` ASC ";
                
            }
            
            else if($filter==='MANAGER'){
                
                if(isset($data['date'])){
                    $CurrDate=$data['date'];
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
        
        
        
        public function add_user($data){
            
            if(empty($data['user'])){
                return json_encode(array("Status"=>"Failed","Mess"=>"Invalid Data"));;
            }
            
            
            $user= json_decode($data['user'],true);
            
            // if(!isset($user['Mobile']) || !isset($user['Name']) || !isset($user['EmployeeID']) || !isset($user['Employer']) || !isset($user['Department']) || !isset($user['Position']) || !isset($user['Permission']) || !isset($user['Manager']) || !isset($user['ManagerID']) || !isset($user['LeaveCount']) || !isset($user['DOJ']) || !isset($user['Sex']) || !isset($user['DOB']) || !isset($user['AL1']) || !isset($user['AL2']) || !isset($user['AL3']) || !isset($user['Zip']) || !isset($user['BG']) || !isset($user['EmName']) || !isset($user['EmNum'])){
            if(!isset($user['Mobile']) || !isset($user['Name']) || !isset($user['EmployeeID']) || !isset($user['Employer']) || !isset($user['Department']) || !isset($user['Position']) || !isset($user['Permission']) || !isset($user['Manager']) || !isset($user['ManagerID']) || !isset($user['LeaveCount'])){
                    
                return json_encode(array("Status"=>"Failed","Mess"=>"Incomplete Data"));
            }
            
            // echo $user['Mobile'];
            // echo $usermobile;
            
            // return;
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin'";
            
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
    
                    $pin = rand(100000, 999999);
                    $hpin = password_hash($pin, PASSWORD_DEFAULT);
                    
                    // echo $rowCount;
                    
                    if($rowCount==0){
                        
                        
                        // $query = "INSERT INTO `ExpenseUsers` (`Name`, `Mobile`, `EmployeeID`, `Manager`, `ManagerID`, `Position`, `Department`, `Pin`, `Permission`, `LeaveCount`, `LeaveBalance`) VALUES ('$user[Name]','$user[Mobile]','$user[EmployeeID]','$user[Manager]','$user[ManagerID]','$user[Position]','$user[Department]','1111','$user[Permission]','$user[LeaveCount]','$user[LeaveCount]')";
                        
                        $query = "INSERT INTO `UserInfo` (`Name`, `Mobile`,`Email`,`EmployeeID`,`Employer`,`Pin`, `Permission`, `resetpassword`) VALUES ('$user[Name]','$user[Mobile]', '$user[Email]','$user[EmployeeID]','$user[Employer]','$hpin','$user[Permission]','TRUE')";
                        $stm = $this->conn->prepare($query);
                        if($stm->execute()===TRUE){

                            $query = "SELECT * FROM `UserInfo` WHERE `Mobile` = '$user[Mobile]' AND `Employer`='$user[Employer]'";
                            $stm = $this->conn->prepare($query);
                            $stm->execute();
                            $row = $stm->fetch(PDO::FETCH_ASSOC);
                            $userid = $row['ID'];

                            $query = "INSERT INTO `EmployeeInfo` (`UserID`,`Name`, `Mobile`, `EmployeeID`,`Employer`, `DOJ`, `Manager`, `ManagerID`, `Position`,`Department`, `LeaveCount`, `ImageFile`, `Status`) VALUES ('$userid','$user[Name]','$user[Mobile]','$user[EmployeeID]','$user[Employer]','$user[DOJ]','$user[Manager]','$user[ManagerID]','$user[Position]','$user[Department]', '$user[LeaveCount]','Def','ACTIVE');
                                    INSERT INTO `PersonalData` (`UserID`,`Name`,`Mobile`,`Employer`,`Sex`, `DOB`, `AddL1`, `AddL2`, `AddL3`, `Zip`, `BloodGroup`, `EmContactName`, `EmContactNum`, `BankName`, `AccNum`,`PAN`) VALUES ('$userid','$user[Name]','$user[Mobile]','$user[Employer]','$user[Sex]','$user[DOB]','$user[AL1]','$user[AL2]','$user[AL3]','$user[Zip]','$user[BG]','$user[EmName]','$user[EmNum]','$user[BankName]','$user[AccNo]','$user[PAN]');";
                        
                            $stm = $this->conn->prepare($query);
                            if($stm->execute()===TRUE){
                                $controller = new EmailController();
                                $status = $controller->sendEmail($user['Name'],$user['Email'],$pin,"newuser");
                                return json_encode(array("Status"=>"Success","Mess"=>"User added successfully"));
                            }
                            else{
                                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to add user"));
                            }
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
        
        
        public function updatePersonalInfo($data){
            
            if(empty($data['user'])){
                return;
            }
            
            
            $user= json_decode($data['user'],true);
            
            if(!isset($user['Mobile']) || !isset($user['Sex']) || !isset($user['DOB']) || !isset($user['AL1']) || !isset($user['AL2']) || !isset($user['AL3']) || !isset($user['Zip']) || !isset($user['BG']) || !isset($user['EmName']) || !isset($user['EmNum']) || !isset($user['BankName']) || !isset($user['AccNo']) || !isset($user['PAN']) || !isset($user['UAN']) || !isset($user['ESI'])){
                return;
            }
            
            
            $query = "UPDATE `PersonalData` SET `Sex`='$user[Sex]',`DOB`='$user[DOB]',`AddL1`='$user[AL1]',`AddL2`='$user[AL2]',`AddL3`='$user[AL3]',`Zip`='$user[Zip]',`BloodGroup`='$user[BG]',`EmContactName`='$user[EmName]',`EmContactNum`='$user[EmNum]',`BankName`='$user[BankName]',`AccNum`='$user[AccNo]',`UAN`='$user[UAN]',`PAN`='$user[PAN]',`ESICNo`='$user[ESI]' WHERE `Mobile`='$user[Mobile]' AND `Employer`='$data[emp]'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Details updated successfully";
            }
            else{
                return "Failed to update details";
            }
            
        }
    
    
        public function resetPassword($data){
            
            $adminmobile=trim($data['adminmobile']);
            $usermobile=trim($data['usermobile']);
            $useremail=trim($data['useremail']);
            $username=trim($data['username']);
            $emp=trim($data['emp']);
    
            $pin = rand(100000, 999999);
            $hpin = password_hash($pin, PASSWORD_DEFAULT);
    
            $query = "SELECT * FROM `UserInfo` WHERE `Employer` = '$emp' AND Permission = 'Admin' AND Mobile = '$adminmobile'";
                    
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            // echo $rowCount;
            
            if($rowCount!=0){
    
                // $status = $this->sendSMS($usermobile,$pin);
                // if($useremail===""){
                //     $status = $this->sendSMS($usermobile,$pin);
                // }
                // else{
                    
                // }
                if($useremail===""){
                    return "Please update email for this user";
                }
                
       
    
                
                $qry = "UPDATE `UserInfo` SET `Pin`='$hpin',`resetpassword`='TRUE' WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";
                $stm = $this->conn->prepare($qry);  
                if($stm->execute()===TRUE){
                    echo "OTP shared to registered mailid";
                    $controller=new EmailController();
                    $controller->sendEmail($username,$useremail,$pin,"reset");
                }
                else{
                    return "Failed";
                }
                
            }
            else{
                return "Failed";
            }
        }
    
    
    
        
        
        public function change_password($data){
            
            
            if(!isset($data['reset']) || !isset($data['usermobile']) || !isset($data['newpass']) || !isset($data['id'])){
                return;
            } 
            
            
            $userpass=trim($data['newpass']);
            $usermobile=trim($data['usermobile']);
            $oldpass=trim($data['oldpass']);
            $id=trim($data['id']);
            $reset=trim($data['reset']);
            $emp=trim($data['emp']);
    
            $pin = password_hash($userpass, PASSWORD_DEFAULT);
    
                if($reset==='true'){
                        
                    //process for password reset by admin
                    $query = "SELECT * FROM `UserInfo` WHERE `Employer` = '$emp' AND Permission = 'Admin'";
                    
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    $rowCount = $stm->rowCount();
                    
                    // echo $rowCount;
                    
                    if($rowCount!=0){
                        $qry = "UPDATE `UserInfo` SET `Pin`='$pin',`resetpassword`='TRUE' WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";
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
                        $qry = "UPDATE `UserInfo` SET `Pin`='$pin', `Tocken`='$id', `resetpassword`='FALSE' WHERE `Mobile`='$usermobile' AND `Employer` = '$emp'";
                        $stm = $this->conn->prepare($qry);  
                        if($stm->execute()===TRUE){
                            return "Completed";
                        }
                    }
                    else{
                        
                        //process for password change by user
                        $query = "SELECT * FROM `UserInfo` WHERE Mobile = '$usermobile' AND `Employer` = '$emp'";
                        
                        $stm = $this->conn->prepare($query);
                        $stm->execute();
                        $rowCount = $stm->rowCount();
                        $row = $stm->fetch(PDO::FETCH_ASSOC);
    
                        if($oldpass===$row['Pin'] || password_verify($oldpass,$row['Pin'])){
                            $qry = "UPDATE `UserInfo` SET `Pin`='$pin' WHERE `Mobile`='$usermobile' AND `Employer` = '$emp'";
                            $stm = $this->conn->prepare($qry);  
                            if($stm->execute()===TRUE){
                                return "Completed";
                            }
                        }   
                    }
                }
        }
        
        
        public function searchDirectory($data){
            
            if(!isset($data['filter']) || !isset($data['emp'])){
                return;
            } 
            
            
            $filter=trim($data['filter']);
            $emp=trim($data['emp']);
            
            // echo $filter;
            
            $query = "SELECT UserID,`Name`,Mobile,EmployeeID,Manager,Position,Department,ImageFile FROM `EmployeeInfo` WHERE Status='ACTIVE' AND Employer='$emp' AND (LOWER(Name) LIKE LOWER('%$filter%') OR LOWER(Department) LIKE LOWER('%$filter%') OR Mobile LIKE '%$filter%' OR LOWER(Position) LIKE LOWER('%$filter%'))";
            
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
        
        public function updateBasicDetails($data){
            
            if(!isset($data['manager']) || !isset($data['managerid']) || !isset($data['position']) || !isset($data['department']) || !isset($data['permission']) || !isset($data['mobile']) || !isset($data['doj']) || !isset($data['leave'])){
                return;
            }
            
            $email="";
            if(isset($data['email'])){
                $email=trim($data['email']);
            }
            
            $manager=trim($data['manager']);
            $managerid=trim($data['managerid']);
            $position=trim($data['position']);
            $department=trim($data['department']);
            $permission=trim($data['permission']);
            $mobile=trim($data['mobile']);
            $doj=trim($data['doj']);
            $leave=trim($data['leave']);
            $emp=trim($data['emp']);
            
            $query = "UPDATE `EmployeeInfo` SET `Manager`='$manager',`ManagerID`='$managerid',`Position`='$position',`Department`='$department',`DOJ`='$doj',`LeaveCount`='$leave' WHERE `Mobile`='$mobile'; UPDATE `UserInfo` SET `Permission`='$permission' WHERE `Mobile`='$mobile' AND `Employer` = '$emp';
                        UPDATE `UserInfo` SET `Email`='$email' WHERE `Mobile`='$mobile' AND `Employer` = '$emp';";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Details updated successfully";
            }
            else{
                return "Failed to update details";
            }
        }
        
        public function checkPersonalInfo($data){
            
            //Used in profile page
            
            if(!isset($data['emp']) || !isset($data['mobile'])){
                return;
            } 
            
            
            $emp=trim($data['emp']);
            $mobile=trim($data['mobile']);
            
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
        
        public function deactivateEmployee($data){
            if(!isset($data['device']) || !isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $device=trim($data['device']);
            $mobile=trim($data['mobile']);
            $emp=trim($data['emp']);
            
            
            $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $query = "SELECT * FROM `EmployeeInfo` WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                
                if($rowCount==1){
                    $row = $stm->fetch(PDO::FETCH_ASSOC);
                    if($row['Status']==='ACTIVE'){
                        $pin = rand(100000, 999999);
                        $hpin = password_hash($pin, PASSWORD_DEFAULT);
                        $query = "UPDATE `EmployeeInfo` SET Status='INACTIVE' WHERE `Mobile`='$mobile' AND `Employer`='$emp';
                                    UPDATE `userinfo` SET Pin='$hpin' WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                    }
                    else{
                        $query = "UPDATE `EmployeeInfo` SET Status='ACTIVE' WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                    }
                    
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


        public function activateEmployee($data){
            if(!isset($data['device']) || !isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $device=trim($data['device']);
            $mobile=trim($data['mobile']);
            $emp=trim($data['emp']);

            $pin = rand(100000, 999999);
            $hpin = password_hash($pin, PASSWORD_DEFAULT);
            
            
            $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $rowCount = $stm->rowCount();
                if($rowCount==1){

                    if($row['Email']===''){
                        return "Update Email to send OTP";
                    }

                    $query = "UPDATE `EmployeeInfo` SET Status='ACTIVE' WHERE `Mobile`='$mobile' AND `Employer`='$emp'";
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){

                        $qry = "UPDATE `UserInfo` SET `Pin`='$hpin',`resetpassword`='TRUE' WHERE `Mobile`='$mobile' AND `Employer` = '$emp'";
                        $stm = $this->conn->prepare($qry);  
                        if($stm->execute()===TRUE){
                            echo "OTP shared to registered mailid";
                            $controller=new EmailController();
                            $controller->sendEmail($row['Name'],$row['Email'],$pin,"reset");
                        }

                    
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
    
    
        public function getDepartments($data){
            
            if(!isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $usermobile=trim($data['mobile']);
            $emp=trim($data['emp']);
    
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
    
        public function getPositions($data){
            
            if(!isset($data['mobile']) || !isset($data['emp'])){
                return;
            } 
            
            $usermobile=trim($data['mobile']);
            $emp=trim($data['emp']);
    
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
        
        

        



    }


?>