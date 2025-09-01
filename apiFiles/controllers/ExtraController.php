<?php

require_once '/var/private_files/controllers/EmailController.php';

    class ExtraController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function getPendingActions($data){

            
            
            if (!isset($data["usermobile"]) || !isset($data['per']) || !isset($data['emp'])) {
                return;
            }    
            
            $usermobile=trim($data['usermobile']);
            $permission=trim($data['per']);
            $emp=trim($data['emp']);


            $query = "SELECT `Status` FROM `Settings` WHERE `Employer`='$data[emp]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if($row['Status']==='0'){
                return json_encode([array("Type"=>"Account is DEACTIVATED","Count"=>"")]);
            }

            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
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


        public function getDistrict($data){

            $state=trim($data['state']);
    
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


        public function getQuote($data){

            if (!isset($data["usermobile"]) || !isset($data["empCount"])) {
                return;
            }
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $empCount=trim($data['empCount']);
    
            $query = "SELECT * FROM `UserInfo` WHERE `Employer`='$emp' AND `Mobile`='$usermobile'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $employerName=$row['Name'];
                $contactEmail=$row['Email'];
            
            }
            else{
                return "Failed";
            }

            $controller= new EmailController();
            $controller->requestQuote($employerName,$contactEmail,$usermobile,$emp,$empCount);
            
        
        }


        public function getZip($data){
        
            if (!isset($data["usermobile"]) || !isset($data['emp'])) {
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
            $CurrDate=date('Ymd');
    
            $users=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $fromDate=trim($data['fromDate']);
            $toDate=trim($data['toDate']);
            $item=trim($data['type']);
    
    
    
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
        
        
        public function getDashboardSummary($data){
            
            if (!isset($data["usermobile"]) || !isset($data['permission']) || !isset($data['emp'])) {
                return;
            }    
            
            $usermobile=trim($data['usermobile']);
            $permission=trim($data['permission']);
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
         public function get_tracker($data){
            
            $device=trim($data['device']);
            $item=trim($data['item']);
            $fromDate=trim($data['fromDate']);
            $toDate=trim($data['toDate']);
            $emp=trim($data['emp']);
            $users=trim($data['users']);
    
            $bulkUsers = json_decode($users);
            $userValues = "'".implode("','", $bulkUsers)."'";
                
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission = 'Admin' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
            
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
        
        
        public function getAccounts($data){
            
            if (!isset($data["device"]) || !isset($data["emp"])) {
                return;
            }
            
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            
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
    
        public function getSettings($data){
            if (!isset($data["param"]) || !isset($data["emp"])) {
                return;
            }
    
            $param=trim($data["param"]);
            $emp=trim($data["emp"]);
    
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


        public function getAllSettings($data){
            if (!isset($data["emp"])) {
                return;
            }
    
            $emp=trim($data["emp"]);
    
            $query = "SELECT * FROM `Settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                return json_encode($row);  
            }
            else{
                return "Failed";
            }
    
        }






    }


?>