<?php

    require_once '/var/private_files/controllers/FCMController.php';

    class AttendanceController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }


        public function getMonthlyAttendance($data){


            if(!isset($data['usermobile']) || !isset($data['month']) || !isset($data['year']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $month=trim($data['month']);
            $year=trim($data['year']);
            $emp=trim($data['emp']);

            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`= '$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
    
            // date_default_timezone_set('Asia/Kolkata');
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


        public function get_attendanceStatus($data){
        
           
        
            if(!isset($data['usermobile'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);

            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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


        public function markAbsent($data){
        
            if(!isset($data['usermobile']) || !isset($data['date']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $date=trim($data['date']);
            $emp=trim($data['emp']);
    
            $query = "UPDATE `Attendance` SET PosLat=0.0,PosLong=0.0,InTime='00:00:00',PosLat2=0.0,PosLong2=0.0,OutTime='00:00:00',Status='Absent',Location='Absent' WHERE Mobile='$usermobile' AND Date='$date'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()){
                return "Success";
            }
            else{
                return "$date";
            }
    
        }



        public function post_Attendance($data){
        
            if(!isset($data['usermobile']) || !isset($data['username']) || !isset($data['posLat']) || !isset($data['posLong']) || !isset($data['location']) || !isset($data['type'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $posLat=trim($data['posLat']);
            $posLong=trim($data['posLong']);
            $location=trim($data['location']);
            $type=trim($data['type']);
            
            $qry = "SELECT * FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
            $activityAttendance = $tz['ActivityAttendance'];

            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
            $Time=date('His');
            $CurrDate=date('Y-m-d');

            if($activityAttendance==='1'){

                $query = "SELECT * FROM `Activity` WHERE Mobile = '$usermobile' AND ActivityDate = '$CurrDate' AND `Date` = '$CurrDate'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();

                if($rowCount<1){
                    return json_encode(array("Status"=>"Failed","Mess"=>"Add activity to mark attendance"));
                }

            }
        
            
            
            
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



        public function get_AttendanceData($data){
        
            if(!isset($data['usermobile']) || !isset($data['month']) || !isset($data['year'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $month=trim($data['month']);
            $year=trim($data['year']);
            
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



        public function getRegularization($data){
        
            if(!isset($data['usermobile']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
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


        public function postRegularization($data){
        
            if(!isset($data['usermobile']) || !isset($data['username']) || !isset($data['date']) || !isset($data['comments']) || !isset($data['regIn']) || !isset($data['regOut'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $date=trim($data['date']);
            $comments=trim($data['comments']);
            $regIn=trim($data['regIn']);
            $regOut=trim($data['regOut']);
            $emp="";
            if(isset($data['emp'])){
                $emp=$data['emp'];
            }
            
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
                        $controller = new FCMController($this->conn);
                        $tokens=$controller->getManagerAdminFCM($emp,$usermobile);
                        if($tokens!="Failed"){
                            foreach ($tokens as $token) {
                                $controller->send_fcm_message($token,'Regilarization',$username.' submitted a request');
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
     
            
        }



        public function approveRegularization($data){
            // echo $status;
            // return;
            
            if(!isset($data['usermobile']) || !isset($data['date']) || !isset($data['status'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $date=trim($data['date']);
            $status=trim($data['status']);
            
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
                    $controller = new FCMController($this->conn);
                    $token=$controller->getUserFCM($usermobile);
                    $controller->send_fcm_message($token,'Regularization '.$status,'Request for '.$date.' is '.$status);
    
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



    }


?>