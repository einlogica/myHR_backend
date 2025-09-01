<?php

    require_once '/var/private_files/controllers/FCMController.php';

    class LeaveController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }


        public function getLeave($data){
        
            if(!isset($data['usermobile']) || !isset($data['year']) || !isset($data['type']) || !isset($data['emp'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $year=trim($data['year']);
            $type = trim($data['type']);
            $emp = trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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
                    $month = trim($data['month']);
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
        
        public function deleteLeave($data){
            
            if(!isset($data['id']) || !isset($data['type'])){
                return;
            } 
            
            $id=trim($data['id']);
            $type=trim($data['type']);
            
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
       
        
        
        public function postLeave($data){
            
            if(!isset($data['username']) || !isset($data['usermobile']) || !isset($data['days']) || !isset($data['leavedate']) || !isset($data['comments'])){
                return;
            }
            
            $username=trim($data['username']);
            $usermobile=trim($data['usermobile']);
            $days=trim($data['days']);
            $leavedate=trim($data['leavedate']);
            $comments=trim($data['comments']);
            $emp=trim($data['emp']);
            $whichhalf=trim($data['WhichHalf']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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
                
                
                $query = "INSERT INTO `LeaveTracker` (`Mobile`, `Name`, `LeaveDate`, `Days`,`LOP`, `AppliedTime`, `AppliedDate`,`Comments`,`Status`,`WhichHalf`) VALUES ";
                $values = [];
                if($days<=1){
                    foreach ($bulkDates as $data) {
                        $v = "('$usermobile','$username','$data','$days','0','$Time','$CurrDate','$comments','Applied','$whichhalf')";
                        $values[] = $v;
                    }
                }
                else{
                    foreach ($bulkDates as $data) {
                        $v = "('$usermobile','$username','$data','1','0','$Time','$CurrDate','$comments','Applied','$whichhalf')";
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
                    $controller = new FCMController($this->conn);
                    $tokens=$controller->getManagerAdminFCM($emp,$usermobile);
                    if($tokens!="Failed"){
                        foreach ($tokens as $token) {
                            $controller->send_fcm_message($token,'Leave',$username.' sumbitted a leave request');
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
        
        
        public function updateLeave($data){
            
            if(!isset($data['id']) || !isset($data['per']) || !isset($data['status']) || !isset($data['comments'])){
                return;
            }
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$data[emp]'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('His');
            $CurrDate=date('Y-m-d');
            
            $id=trim($data['id']);
            $per=trim($data['per']);
            $status=trim($data['status']);
            $comments=trim($data['comments']);
            // $emp=trim($data['emp']);
            
            
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
                            $controller = new FCMController($this->conn);
                            $controller->send_fcm_message($token,'Leave '.$status,$per.' has '.$status. ' your request');
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
                            $controller = new FCMController($this->conn);
                            $controller->send_fcm_message($token,'Leave '.$status,$per.' has '.$status. ' your request');
                        }
                    }
                    
    
                    return "Success";
                }
                else{
                    return "Failed";
                }
            }
            
        }

        



    }


?>