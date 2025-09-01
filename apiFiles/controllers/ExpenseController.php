<?php

    class ExpenseController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function getMonthlyExpense($data){

            if(!isset($data['usermobile']) || !isset($data['month']) || !isset($data['year']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $month=trim($data['month']);
            $year=trim($data['year']);
            $emp=trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
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
    
        public function getExpenseType($data){
            if(!isset($data['emp'])){
                return;
            }  
    
            $emp=trim($data['emp']);
    
            $query = "SELECT * FROM `ExpenseType` WHERE Employer = '$emp'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
        }
    
    
        public function getVehicle($data){
            if(!isset($data['usermobile']) || !isset($data['emp'])){
                return;
            }
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
    
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
        
        public function get_billImage($data){
            
            if(!isset($data['filename']) || !isset($data['type']) || !isset($data['emp'])){
                return;
            }
            
            $filename=trim($data['filename']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            
            
            
            // echo "$filename\n";
            
            if($type==="Bill"){
                $path= "/var/private_files/Bill_Images/$emp/".$filename;
            }
            else if($type==="Policy"){
                $path= "/var/private_files/PolicyDocuments/$emp/".$filename;
            }
            else if($type==="Employer"){
                $path= "/var/private_files/Icons/$filename.png";
            }
            else if($type==="Collection"){
                $path= "/var/private_files/Collection/$emp/".$filename;
            }
            else if($type==="Profile"){
                $path= "/var/private_files/Profile_Images/$emp/".$filename;
            }
    
            if($filename!="" && file_exists($path)){
                $byte_array = file_get_contents($path);
                $data = base64_encode($byte_array);
                return "$data";
            }
            else{
                return "Failed";
            }
            
            // echo "$path\n";
            
            
        }
        
        
        
        
        public function clear_bill($data){
            
            
            if(!isset($data['billid']) || !isset($data['status']) || !isset($data['comments'])){
                return;
            }
            
            $billid=trim($data['billid']);
            $status=trim($data['status']);
            $comments=trim($data['comments']);
            
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
        
        
        
        public function delete_bill($data){
            
            if(!isset($data['billid']) || !isset($data['emp'])){
                return;
            } 
            
            $billid=trim($data['billid']);
            $emp=trim($data['emp']);
    
            $query = "SELECT * FROM `ExpenseTracker` WHERE ID = '$billid'";
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            // echo $rowCount;
            
            if($rowCount!=0){
               $billStatus=$stm->fetch(PDO::FETCH_ASSOC); 
               $imageFile=$billStatus['Filename'];
    
               $file_pointer = "/var/private_files/Bill_Images/$emp/$imageFile";
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
    
        public function addEmployeeAdvance($data){
    
            if(!isset($data['id']) || !isset($data['usermobile']) || !isset($data['username']) || !isset($data['date']) || !isset($data['amount'])){
                return;
            } 
            
            $id=trim($data['id']);
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $account=trim($data['account']);
            $location=trim($data['location']);
            $date=trim($data['date']);
            $amount=trim($data['amount']);
            $emp=trim($data['emp']);
    
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
        
        
        public function updateExpense($data){
            
            
            if(!isset($data['id']) || !isset($data['per']) || !isset($data['status']) || !isset($data['comments'])){
                return;
            } 
            
            $id=trim($data['id']);
            $per=trim($data['per']);
            $status=trim($data['status']);
            $comments=trim($data['comments']);
            
            
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
                        $controller = new FCMController($this->conn);
                        $controller->send_fcm_message($token,'Expense '.$status,$per.' has '.$status. ' your request');
                    }
                }
    
                return "Success";
            }
            else{
                return "Failed";
            }
            
            
        }
    
        public function clearExpense($data){
            
            
            if(!isset($data['id']) || !isset($data['per']) || !isset($data['status']) || !isset($data['comments'])){
                return;
            } 
            
            $id=trim($data['id']);
            $per=trim($data['per']);
            $status=trim($data['status']);
            $comments=trim($data['comments']);
    
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
    
        public function addDailyWages($data){
            
            if(!isset($data['usermobile']) || !isset($data['username']) || !isset($data['labour']) || !isset($data['labourcount']) || !isset($data['amount'])){
                return;
            } 
            
            
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $site=trim($data['site']);
            $labourname=trim($data['labour']);
            $labourcount=trim($data['labourcount']);
            $duration=trim($data['duration']);
            $amount=trim($data['amount']);
            $date=trim($data['date']);
            $emp="";
    
    
            $query = "INSERT INTO `ExpenseTracker` (`Mobile`,`Name`, `Site`, `LabourName`, `LabourCount`, `Duration`, `Date`, `Type`, `Amount`,`Status`) VALUES ('$usermobile','$username','$site','$labourname','$labourcount','$duration','$date','Daily-Wage','$amount','Applied')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $controller = new FCMController($this->conn);
                $tokens=$controller->getManagerAdminFCM($emp,$usermobile);
                if($tokens!="Failed"){
                    foreach ($tokens as $token) {
                        $controller->send_fcm_message($token,'Daily Wage - Rs '.$amount.'/-',$username.' sumbitted an expense claim');
                    }
                }
    
                return "Success";
            }
            else{
                return "Failed";
            }
            
            
            
        }
        
        
        public function upload_bill($data){
            
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['username']) || !isset($data['billamount'])){
                return;
            } 
            
            $emp=$data['emp'];
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $type=trim($data['type']);
            $site=trim($data['site']);
            $item=trim($data['item']);
            // $labourname=trim($data['labourname']);
            $fromLoc=trim($data['fromLoc']);
            $toLoc=trim($data['toLoc']);
            $km=trim($data['km']);
            $billno=trim($data['billno']);
            $billamount=trim($data['billamount']);
            $billdate=trim($data['billdate']);
            $file=$data['file'];
            $fileavailable=trim($data['fileavailable']);
            
            return $this->process_uploadBill($emp,$usermobile,$username,$type,$item,0,'NA',$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable);
            
            
            
        }
        
        
        public function process_uploadBill($emp,$usermobile,$username,$type,$item,$shop,$shopdesc,$site,$fromLoc,$toLoc,$km,$billno,$billamount,$billdate,$file,$fileavailable){
    
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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
            
            $target_dir ="/var/private_files/Bill_Images/$emp/";
            
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
                        $controller = new FCMController($this->conn);
                        $tokens=$controller->getManagerAdminFCM($emp,$usermobile);
                        if($tokens!="Failed"){
                            foreach ($tokens as $token) {
                                $controller->send_fcm_message($token,'Expense - Rs '.$billamount.'/-',$username.' sumbitted an expense claim');
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
                        $controller = new FCMController($this->conn);
                        $tokens=$controller->getManagerAdminFCM($emp,$usermobile);
                        if($tokens!="Failed" && $type!="Salary Advance"){
                            foreach ($tokens as $token) {
                                $controller->send_fcm_message($token,'Expense - Rs '.$billamount.'/-',$username.' sumbitted an expense claim');
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
    
    
        public function upload_purchasebill($data){
            
            // echo "point 1";
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['username']) || !isset($data['billamount'])){
                return;
            } 
            // echo "point 2";
            
            $emp=trim($data['emp']);
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $type=trim($data['type']);
            $billno=trim($data['billno']);
            $billamount=trim($data['billamount']);
            $billdate=trim($data['billdate']);
            $file=$data['file'];
            $fileavailable=trim($data['fileavailable']);
            
            $item=trim($data['item']);
            $site=trim($data['site']);
            
            
            $id=trim($data['id']);
            
            $shop=trim($data['shop']);
            
            // echo "point 3";
            
            
            if($id===""){
    
                
                $l1=trim($data['l1']);
                $l2=trim($data['l2']);
                $l3=trim($data['l3']);
                $dist=trim($data['dist']);
                $phone=trim($data['phone']);
                $gst=trim($data['gst']);
                
                
                $query = "SELECT * from `BillerTracker` WHERE GST = '$gst' AND GST != '' AND Employer='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
                if($rowCount!=0){
                    echo json_encode(array("Status"=>"Failed","Mess"=>"Duplicate GST"));
                    return;
                }
                else{
    
                    $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
                    $stm = $this->conn->prepare($qry);
                    $stm->execute();
                    $tz = $stm->fetch(PDO::FETCH_ASSOC);
                    $timezone = $tz['TimeZone'];
                
                    date_default_timezone_set($timezone);
                    // date_default_timezone_set('Asia/Kolkata');
        
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
    
        
        
        
        public function get_userexpense($data){
            
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['mon']) || !isset($data['year']) || !isset($data['type'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $mon=trim($data['mon']);
            $year=trim($data['year']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            
            // $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            // $stm = $this->conn->prepare($qry);
            // $stm->execute();
            // $tz = $stm->fetch(PDO::FETCH_ASSOC);
            // $timezone = $tz['TimeZone'];
        
            // date_default_timezone_set($timezone);
            // // date_default_timezone_set('Asia/Kolkata');
        
            // $Time=date('His');
            // $CurrDate=date('Y-m-d');
            // $pre_date = date('Y-m-d', strtotime("-20 days"));
            
           
            
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
    
    
    
        public function get_preexpense($data){
            
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['mon']) || !isset($data['year']) || !isset($data['type'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $mon=trim($data['mon']);
            $year=trim($data['year']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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
    
        
        public function getBiller($data){
            
            if(!isset($data['emp']) || !isset($data['filter']) || !isset($data['type'])){
                return;
            } 
            
            $filter=trim($data['filter']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            
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
        
        
        
    
    
        public function addBiller($data){
    
            if(!isset($data['emp']) || !isset($data['shop']) || !isset($data['l1']) || !isset($data['dist']) || !isset($data['phone'])){
                return;
            } 
            
            $shop=trim($data['shop']);
            $l1=trim($data['l1']);
            $l2=trim($data['l2']);
            $l3=trim($data['l3']);
            $dist=trim($data['dist']);
            $phone=trim($data['phone']);
            $gst=trim($data['gst']);
            $div=trim($data['div']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            $username=trim($data['username']);
            $usermobile=trim($data['usermobile']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
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

        



    }


?>