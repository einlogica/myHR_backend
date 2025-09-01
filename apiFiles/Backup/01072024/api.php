<?php

require '/var/private_expense/php-jwt/vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

error_reporting(E_ALL);
ini_set('display_errors', '1');

class call_api{
    
    private $conn;
    
    public function __construct($db){
        $this->conn=$db;
    }
    
    

    //===============================================================================================================================================================USERS
    

    public function login_fun(){
        
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
        
        $appVersion ="V1";
        $secretKey = 'jilaritechnologies-jwt-tocken';
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        
        if($appVersion!=$app){
            return json_encode(array("Data"=>"App version check failed","Status"=>"Failed"));
        }
        else{
            
            $query = "SELECT `Pin` FROM `Userinfo` Where `Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if($row['Pin']===$userpass || password_verify($userpass,$row['Pin'])){
                
                $query ="SELECT `UserInfo`.Name,`UserInfo`.Mobile,`UserInfo`.EmployeeID,`UserInfo`.Employer,`UserInfo`.Permission,`UserInfo`.resetpassword,`EmployeeInfo`.Department,`EmployeeInfo`.Position,`EmployeeInfo`.Manager,`EmployeeInfo`.ManagerID,`EmployeeInfo`.DOJ,`UserInfo`.Tocken,`EmployeeInfo`.ImageFile,`EmployeeInfo`.LeaveCount,`EmployeeInfo`.`Status` FROM `UserInfo` LEFT JOIN `EmployeeInfo` ON `UserInfo`.Mobile=`EmployeeInfo`.Mobile WHERE `UserInfo`.Mobile = '$usermobile' AND `EmployeeInfo`.`Status`!='DEACT'";
            
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
                    return json_encode(array("Data"=>"Login Failed","Status"=>"Failed"));
                }

            }
            else{
                return json_encode(array("Data"=>"Credentials Failed","Status"=>"Failed"));
            }
	               
            
        }
        
        // $conn->close();

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
            
            $query = "SELECT `EmployeeInfo`.`Name`,`EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Position`,`Att`.`Status`,`Att`.`Location`,`EmployeeInfo`.`Status` FROM `EmployeeInfo` LEFT JOIN (SELECT `Mobile`,`Status`,`Location` FROM `Attendance` WHERE `Date`='$CurrDate') AS Att ON `EmployeeInfo`.`Mobile`=`Att`.`Mobile` WHERE `EmployeeInfo`.`Status`= 'ACTIVE' AND `EmployeeInfo`.`Employer`='$emp' ORDER BY `EmployeeInfo`.`Name` ASC";
            
        }
        
        else if($filter==='MANAGER'){
            
            $query = "SELECT `EmployeeInfo`.`Name`,`EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Position`,`Att`.`Status`,`Att`.`Location`,`EmployeeInfo`.`Status` FROM `EmployeeInfo` LEFT JOIN (SELECT `Mobile`,`Status`,`Location` FROM `Attendance` WHERE `Date`='$CurrDate') AS `Att` ON `EmployeeInfo`.`Mobile`=`Att`.`Mobile` WHERE `EmployeeInfo`.`Status`= 'ACTIVE' AND `EmployeeInfo`.`Employer`='$emp' AND `EmployeeInfo`.`ManagerID` in (SELECT EmployeeID FROM `EmployeeInfo` Where `Mobile`='$mobile') ORDER BY `EmployeeInfo`.`Name` ASC";
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
            return;
        }
        
        
        $user= json_decode($_POST['user'],true);
        
        if(!isset($user['Mobile']) || !isset($user['Name']) || !isset($user['EmployeeID']) || !isset($user['Employer']) || !isset($user['Department']) || !isset($user['Position']) || !isset($user['Permission']) || !isset($user['Manager']) || !isset($user['ManagerID']) || !isset($user['LeaveCount']) || !isset($user['DOJ']) || !isset($user['Sex']) || !isset($user['DOB']) || !isset($user['AL1']) || !isset($user['AL2']) || !isset($user['AL3']) || !isset($user['Zip']) || !isset($user['BG']) || !isset($user['EmName']) || !isset($user['EmNum'])){
            return;
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
                    if($stm->execute()){
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
                $query = "SELECT * FROM `UserInfo` WHERE Tocken = '$id' AND Permission = 'Admin'";
                
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
    
    
    
    //===========================================================================================================================================================================ATTENDANCE
    
    
    public function get_attendanceStatus(){
        
        
        
        if(!isset($_POST['usermobile'])){
            return;
        } 
        
        $usermobile=trim($_POST['usermobile']);
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('H:i:s');
        $CurrDate=date('Y-m-d');
        
        $query = "SELECT * FROM `Attendance` WHERE Mobile = '$usermobile' AND Date = '$CurrDate'";
            
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        if($rowCount==1){
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
                    return json_encode(array("Status"=>"Attendance applied","Mess"=>"Attendance has been applied"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
                } 
            }
            else{
                return json_encode(array("Status"=>"Attendance applied","Mess"=>"Attendance already applied"));
            }

            
        }
        else if($type==='CheckOut'){
            $query = "UPDATE `Attendance` SET `PosLat2`='$posLat',`PosLong2`='$posLong',`OutTime`='$Time' WHERE `Mobile`='$usermobile' AND `Date`='$CurrDate'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()){
                return json_encode(array("Status"=>"Attendance applied","Mess"=>"Attendance has been applied"));
            }
            else{
                return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
            }
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
    
    
    public function updateAttendance(){
        
        if(!isset($_POST['usermobile']) || !isset($_POST['date'])){
            return;
        }
        
        $usermobile=trim($_POST['usermobile']);
        $date=trim($_POST['date']);
        
        // echo $date;
        $formattedDate = date_format(date_create("$date"),"Y-m-d");
        // echo $formattedDate;
        // return;
        
        $query = "UPDATE `Attendance` SET Location = 'Regularized' WHERE Mobile = '$usermobile' AND Date='$formattedDate'";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            return "Regularization applied";   
        }
        else{
            return "Failed to apply regularization";
        }
        
    }
    
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
        else{
            $query = "UPDATE `Regularization` SET `InTime`='$regIn', `OutTime`='$regOut', `Comments`='$comments' WHERE `Mobile`='$usermobile' AND `Date`='$formattedDate'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return "Request updated successfully";
            }
            else{
                return "Failed to update regularization";
            }
        }
        
        
        
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
        else{
            $path= "../../private_files/Profile_Images/$emp/".$filename;
        }
        
        // echo "$path\n";
        
        $byte_array = file_get_contents($path);
        $data = base64_encode($byte_array);
        return "$data";
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
           if(strlen($imageFile)>10){
               $file_pointer = "../../private_files/Bill_Images/$emp/$imageFile";
               if (!unlink($file_pointer)) {
                    return "$file_pointer cannot be deleted due to an error";
                }
                else{
                    $query = "DELETE FROM `ExpenseTracker` WHERE ID = '$billid'";
        
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        return "Completed";
                    }
                }
           }
           else{
                $query = "DELETE FROM `ExpenseTracker` WHERE ID = '$billid'";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return "Completed";
                } 
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
            
            
            $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`, `Date`, `Type`, `Amount`,`Status`) VALUES ('$usermobile','$username','$location','$date','Advance','$amount','Approved')";
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
        $labourname=trim($_POST['labourname']);
        $fromLoc=trim($_POST['fromLoc']);
        $toLoc=trim($_POST['toLoc']);
        $km=trim($_POST['km']);
        $billno=trim($_POST['billno']);
        $billamount=trim($_POST['billamount']);
        $billdate=trim($_POST['billdate']);
        $file=$_POST['file'];
        $fileavailable=trim($_POST['fileavailable']);
        
        return $this->process_uploadBill($emp,$usermobile,$username,$type,$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable);
        
        
        
    }
    
    
    private function process_uploadBill($emp,$usermobile,$username,$type,$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable){

        // echo $emp;
        // echo $usermobile;
        // echo $username;
        // echo $type;
        // echo $site;
        // echo $fromLoc;
        // echo $toLoc;
        // echo $km;
        // echo $billno;
        // echo $billamount;
        // echo $billdate;
        // echo $file;
        // echo $fileavailable;
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Ymd');
        
        if($type==="Advance" || $type ==="Salary Advance"){
            $status="Approved";
        }
        else{
            $status="Applied";
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
            
            $filename = $usermobile."-".$CurrDate.$Time.".png";
            $target_file = $target_dir . $filename;
            $data = base64_decode($file);      
            
            // move_uploaded_file($file["tmp_name"], $target_file
            
              if (file_put_contents($target_file, $data)) {
                $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','$filename','$status')";
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
                
                
                if($type==='Labour'){
                    $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`LabourName`,`Duration`,`LabourCount`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','NONE','$status')";
                }
                else{
                    $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','NONE','$status')";
                   
                }
                // $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`,`FromLoc`,`ToLoc`,`KM`, `Date`, `Type`, `BillNo`, `Amount`, `Filename`,`Status`) VALUES ('$usermobile','$username','$site','$fromLoc','$toLoc','$km','$billdate','$type','$billno','$billamount','NONE','$status')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return json_encode(array("Status"=>"Success","Mess"=>"Data Inserted"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Data Insert Failed"));
                }
            }   
        
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
            $query = "SELECT ExpenseTracker.*, BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Site=BillerTracker.ID WHERE MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' AND ExpenseTracker.Mobile in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY ExpenseTracker.Date ASC";
        }
        else if($type==='ALL'){
            // $query = "SELECT * FROM ExpenseTracker WHERE Date BETWEEN '$pre_date' AND '$CurrDate' ORDER BY Date ASC";
            $query = "SELECT ExpenseTracker.*, BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Site=BillerTracker.ID WHERE ExpenseTracker.Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' ORDER BY ExpenseTracker.Date ASC";
        }
        else if($type==='EMP'){
            // $query = "SELECT * FROM ExpenseTracker WHERE Mobile = '$usermobile' AND MONTH(Date) = '$mon' AND YEAR(Date)='$year' ORDER BY Date ASC";
            $query = "SELECT ExpenseTracker.*,BillerTracker.ShopName,BillerTracker.District,BillerTracker.Phone,BillerTracker.GST FROM ExpenseTracker LEFT JOIN BillerTracker ON ExpenseTracker.Site=BillerTracker.ID WHERE ExpenseTracker.Mobile = '$usermobile' AND ExpenseTracker.Mobile IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND MONTH(ExpenseTracker.Date) = '$mon' AND YEAR(ExpenseTracker.Date)='$year' ORDER BY ExpenseTracker.Date ASC";
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
        
        
        $id=trim($_POST['id']);
        
        
        // echo "point 3";
        
        
        if($id===""){

            $shop=trim($_POST['shop']);
            $l1=trim($_POST['l1']);
            $l2=trim($_POST['l2']);
            $l3=trim($_POST['l3']);
            $dist=trim($_POST['dist']);
            $phone=trim($_POST['phone']);
            $gst=trim($_POST['gst']);
            
            $query = "SELECT * from `BillerTracker` WHERE GST = '$gst' AND Employer='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            if($rowCount!=0){
                echo json_encode(array("Status"=>"Failed","Mess"=>"Duplicate GST"));
                return;
            }
            else{
                $query = "INSERT INTO `BillerTracker` (`Employer`,`ShopName`,`AddressL1`,`AddressL2`,`AddressL3`,`District`,`Phone`,`GST`) VALUES ('$emp','$shop','$l1','$l2','$l3','$dist','$phone','$gst') ";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $query = "SELECT * from `BillerTracker` WHERE GST = '$gst'";
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    $row = $stm->fetch(PDO::FETCH_ASSOC);
                    $shopid= $row['ID'];
                    
                }
            }
            
        }
        else{
            $shopid=$id;
        }
        
        if($shopid!=""){
            // echo $shopid;
            $status = $this->process_uploadBill($emp,$usermobile,$username,$type,$shopid,"","",0,$billno,$billamount,$billdate,$file,$fileavailable);
            $value = json_decode($status,true);
            // echo $status;
            if($value['Status']==="Success"){
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
            
            $query = "SELECT * FROM `LeaveTracker` WHERE `Mobile` = '$usermobile' AND `Mobile` IN (SELECT Mobile From `EmployeeInfo` WHERE Employer='$emp') AND YEAR(`LeaveDate`)='$year' ORDER BY `LeaveDate` DESC";

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
        
        
        if(($row['L1Status']==='' && $row['L2Status']==='') || $type ==='MAN' || $status === 'Rejected'){
            
            
            // $query = "SELECT * FROM `ExpenseUsers` WHERE `Mobile` = '$mobile'";
            // $stm = $this->conn->prepare($query);
            // $stm->execute();
            // $row = $stm->fetch(PDO::FETCH_ASSOC);
            // $leaveBal=(double)$row['LeaveBalance'];
            
            $query = "DELETE FROM `LeaveTracker` WHERE ID = '$id'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                // $leaveBal=$leaveBal+$halfDay;
                // $query = "UPDATE `ExpenseUsers` SET `LeaveBalance`='$leaveBal' WHERE `Mobile`='$mobile'";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$mobile' AND `Status`!='Rejected' AND YEAR(`LeaveDate`)=YEAR('$leaveDate') ORDER BY `LeaveDate` ASC";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
                $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$mobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$mobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$leaveDate')";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
                
                return json_encode(array("Status"=>"Success","Mess"=>"Leave request deleted"));
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

                
                $query ="SET @serial_number := 0; UPDATE `LeaveTracker` SET `LOP`=(@serial_number := @serial_number + `LeaveTracker`.`Days`) WHERE `Mobile`='$usermobile' AND YEAR(`LeaveDate`)=YEAR('$bulkDates[0]') ORDER BY `LeaveDate` ASC";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
                $ld = ($dateValues);
                
                $query ="UPDATE `LeaveTracker` AS `LT` LEFT JOIN (SELECT `LeaveCount`,`Mobile` FROM `EmployeeInfo` WHERE `Mobile`='$usermobile') AS `ALC` ON `ALC`.`Mobile`=`LT`.`Mobile` SET `LT`.`LOP`=IF(`ALC`.`LeaveCount`-`LT`.`LOP`<0,IF(`ALC`.`LeaveCount`-`LT`.`LOP`=-.5,.5,`LT`.`Days`),0) WHERE `LT`.`Mobile`='$usermobile' AND `LT`.`Status`!='Rejected' AND YEAR(`LT`.`LeaveDate`)=YEAR('$bulkDates[0]')";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
                
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
        
        $id=trim($_POST['id']);
        $per=trim($_POST['per']);
        $status=trim($_POST['status']);
        $comments=trim($_POST['comments']);
        
        
        $bulkID = json_decode($id);
        $idValues = "'".implode("','", $bulkID)."'";
        
        
        if($status==="Rejected"){

            
            if($per == "Manager"){
            $query = "UPDATE `LeaveTracker` SET `L1Status`='$status', `L1Comments`='$comments', `Status`='Rejected' WHERE `ID` IN ($idValues)";
            }
            else if($per == "Admin"){
               $query = "UPDATE `LeaveTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Rejected' WHERE `ID` IN ($idValues)"; 
            }
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                $query ="SET @serial_number := 0;SET @prev_mobile := '';UPDATE `LeaveTracker` SET `LOP`='0' WHERE `Status`='Rejected' AND  `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues));UPDATE `LeaveTracker` SET `LOP`= CASE WHEN `Mobile` = @prev_mobile THEN @serial_number := @serial_number + `Days` ELSE @serial_number := 1 AND @prev_mobile := `Mobile` END WHERE `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues)) AND `Status`!='Rejected' ORDER BY `Mobile` , `LeaveDate` ASC";
                $stm1 = $this->conn->prepare($query);
                $stm1->execute();
                
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
                
                // $query = "SELECT * FROM `Attendance` WHERE `Mobile`='$mobile' AND `Date`='$leaveDate'";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                // $rowCount = $stm->rowCount();
                // if($rowCount!=0){
                //     $query = "UPDATE `Attendance` SET `Location`='Leave',`PosLat`='0.00',`PosLong`='0.00' WHERE `Mobile`='$mobile' AND `Date`='$leaveDate'";
                //     $stm = $this->conn->prepare($query);
                //     $stm->execute();
                // }
                
                // $query = "SELECT * FROM `Attendance` WHERE `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues)) AND `Date` IN (SELECT `LeaveDate` FROM `LeaveTracker` WHERE `ID` IN ($idValues))";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                // $rowCount = $stm->rowCount();
                // if($rowCount!=0){
                //     $query = "UPDATE `Attendance` SET `Location`='Leave',`PosLat`='0.00',`PosLong`='0.00' WHERE `Mobile` IN (SELECT `Mobile` FROM `LeaveTracker` WHERE `ID` IN ($idValues)) AND `Date` IN (SELECT `LeaveDate` FROM `LeaveTracker` WHERE `ID` IN ($idValues))";
                //     $stm = $this->conn->prepare($query);
                //     $stm->execute();
                // }
                
                $query = "UPDATE `Attendance` AS `A` INNER JOIN `LeaveTracker` AS `L` ON `A`.`Mobile`=`L`.`Mobile` AND `A`.`Date`=`L`.`LeaveDate` SET `A`.`Status`= 'Leave' WHERE `L`.`ID` IN ($idValues) AND `A`.`Status`='Absent'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                $query = "UPDATE `LeaveTracker` SET `L2Status`='$status', `L2Comments`='$comments', `Status`='Approved' WHERE `ID` IN ($idValues)"; 
            }
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
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
            $query = "SELECT * FROM `Activity` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `ActivityDate`='$date' ORDER BY ID DESC";
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
        
        
        $query = "INSERT INTO `Activity` (`Mobile`, `Name`,`Type`, `Site`, `Drive`, `StartKM`, `EndKM`, `PosLat`, `PosLong`, `Activity`,`ActivityDate`, `Date`, `Time`,`Customer`,`CustNo`) VALUES ('$usermobile','$username','$type','$site','$drive','$sKM','$eKM','$lat','$long','$activity','$date','$CurrDate','$Time','$cust','$custno')";
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
            
            
            //Calculate number of sundays
            $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
            $lastDay = date('Y-m-t', strtotime($firstDay));
            $totalDays = date('t', strtotime($firstDay));
        
            $sundayCount = 0;
            $currentDate = strtotime($firstDay);
        
            while (date('Y-m-d', $currentDate) <= $lastDay) {
                if (date('l', $currentDate) === 'Sunday') {
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
        
        if(!isset($_POST['username']) || !isset($_POST['usermobile']) || !isset($_POST['amount']) || !isset($_POST['startdate'])){
            return;
        }
        
        $username=trim($_POST['username']);
        $usermobile=trim($_POST['usermobile']);
        $amount=trim($_POST['amount']);
        $emi=trim($_POST['emi']);
        $startdate=trim($_POST['startdate']);
        $emp=trim($_POST['emp']);
        
        date_default_timezone_set('Asia/Kolkata');
    
        $Time=date('His');
        $CurrDate=date('Y-m-d');
        
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
            
            $status = $this->process_uploadBill($emp,$usermobile,$username,"Salary Advance","","","","","",$amount,$CurrDate,"","false");
            
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
        
        
        $query = "SELECT `ID`,'Birthday' AS `Event`,`Name` FROM `PersonalData` WHERE MONTH(`DOB`)=MONTH('$CurrDate') AND DAY(`DOB`)=DAY('$CurrDate') AND `Mobile` IN (SELECT `Mobile` FROM `UserInfo` WHERE `Employer`='$emp')";
        $stm = $this->conn->prepare($query);
        if($stm->execute()===TRUE){
            $DOB = $stm->fetchall(PDO::FETCH_ASSOC);
        }
        else{
            $DOB=array();
        }
        
        $query = "SELECT `ID`,'Anniversary' AS `Event`,`Name` FROM `EmployeeInfo` WHERE MONTH(`DOJ`)=MONTH('$CurrDate') AND DAY(`DOJ`)=DAY('$CurrDate') AND `Employer`='$emp'";
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
                if (!unlink($file_pointer)) {
                     return "$file_pointer cannot be deleted due to an error";
                 }
                 else{
                     $query = "DELETE FROM `PolicyDocs` WHERE `ID`='$id'";
                     $stm = $this->conn->prepare($query);
                     if($stm->execute()===TRUE){
                         return "Success";
                     }
                     else{
                         return "Failed";
                     }
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
    
    
    
    //========================================================================================================================================================================================= OTHER
    
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
            
        
        $query = "SELECT * FROM `UserInfo` WHERE `Tocken` = '$device' AND Permission = 'Admin' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        
        $stm = $this->conn->prepare($query);
        $stm->execute();
        $rowCount = $stm->rowCount();
        
        // echo $rowCount;
        
        if($rowCount!=0){
            
            
            if($item==='Attendance'){
                $query = "SELECT * FROM `Attendance` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            else if($item==='Expense'){
                // $query = "SELECT ExpenseTracker.Mobile,EmployeeInfo.Name,ExpenseTracker.Site,ExpenseTracker.FromLoc,ExpenseTracker.ToLoc,ExpenseTracker.KM,ExpenseTracker.Date,ExpenseTracker.Type,ExpenseTracker.BillNo,ExpenseTracker.Amount,ExpenseTracker.Filename,ExpenseTracker.Status FROM ExpenseTracker INNER JOIN EmployeeInfo WHERE ExpenseTracker.Mobile = EmployeeInfo.Mobile AND ExpenseTracker.Date BETWEEN '$fromDate' AND '$toDate' AND ExpenseTracker.`Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                // $query = "SELECT * FROM ExpenseTracker WHERE Date BETWEEN '$fromDate' AND '$toDate' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                $query = "SELECT ExpenseTracker.*,BT.ShopName,BT.District AS ShopDist,BT.Phone AS ShopPhone,BT.GST AS ShopGST FROM ExpenseTracker LEFT JOIN (SELECT * FROM `BillerTracker` WHERE `Employer`='$emp') AS `BT` ON `ExpenseTracker`.`Site`=`BT`.`ID` AND `ExpenseTracker`.`Type`='Purchase' WHERE `ExpenseTracker`.Date BETWEEN '$fromDate' AND '$toDate' AND `ExpenseTracker`.`Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
        
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            else if($item==='Activity'){
                
                $query = "SELECT * FROM `Activity` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `Date` BETWEEN '$fromDate' AND '$toDate' ORDER BY `Date`";
        
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
            $query = "SELECT `AccName` FROM `Accounts` WHERE `Employer`='$emp'";
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
