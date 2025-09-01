<?php

    require_once '/var/private_files/controllers/ExpenseController.php';

    class PayslipController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }


        public function getSalaryStructure($data){
        
            if(!isset($data['usermobile']) || !isset($data['device'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $device=trim($data['device']);
            
    
            $check = "false";
            
            $query = "SELECT * from `UserInfo` WHERE `Mobile`='$usermobile' AND `Permission` = 'Admin' AND `Employer`='$data[emp]'";
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
        
        
        public function updateSalary($data){
            
            if(!isset($data['usermobile']) || !isset($data['device']) || !isset($data['basic'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $device=trim($data['device']);
            $basic=trim($data['basic']);
            $special    =trim($data['special']);
            $hra=trim($data['hra']);
            $ta=trim($data['ta']);
            $da=trim($data['da']);
            $incentive=trim($data['incentive']);
            $pf=trim($data['pf']);
            $esic=trim($data['esic']);
            $protax=trim($data['protax']);
            
            $query = "SELECT * from `UserInfo` WHERE `Employer`='$data[emp]' AND `Permission` = 'Admin'";
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
        
        
        public function generatePayroll($data){
            
            if(!isset($data['month']) || !isset($data['year']) || !isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            $month=trim($data['month']);
            $year=trim($data['year']);
            $lop=trim($data['lop']);
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            $users=trim($data['users']);
    
            $bulkUsers = json_decode($users);
            $userValues = "'".implode("','", $bulkUsers)."'";
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND `Employer`='$data[emp]'";
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
                $weekoffDays = explode(',', $weekoff);
                $leavestr=$row['LeaveStructure'];
                $OT=$row['OverTime'];
                
                
                //Calculate number of sundays
                $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
                $lastDay = date('Y-m-t', strtotime($firstDay));
                $totalDays = date('t', strtotime($firstDay));
            
                $sundayCount = 0;
                $currentDate = strtotime($firstDay);
            
                while (date('Y-m-d', $currentDate) <= $lastDay) {
                    // if (date('l', $currentDate) === $weekoff) {
                    //     $sundayCount++;
                    // }
                    if (in_array(date('l', $currentDate), $weekoffDays)) {
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
                    $query="UPDATE `PayRollTemplate` SET `Basic`=ROUND(`Basic`-(`Basic`/30*`TotalLOP`),0),`Allowance`=ROUND(`Allowance`-(`Allowance`/30*`TotalLOP`),0),`HRA`=Round(`HRA`-(`HRA`/30*`TotalLOP`),0),`TA`=ROUND(`TA`-(`TA`/30*`TotalLOP`),0),`DA`=ROUND(`DA`-(`DA`/30*`TotalLOP`),0),`Incentive`=ROUND(`Incentive`-(`Incentive`/30*`TotalLOP`),0),`GrossIncome`=(`Basic`+`Allowance`+`HRA`+`TA`+`DA`+`Incentive`),`PF`=ROUND((`PF`/100* IF(`GrossIncome`>15000,15000,`GrossIncome`),0),`ESIC`=Round((`ESIC`/100*`GrossIncome`),0) WHERE `Employer`='$emp'";
                    
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
        
        public function deleteTemplate($data){
            
            if(!isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND `Employer`='$data[emp]'";
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
        
        
        
        public function getPayrollTemplate($data){
            
            if(!isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND `Employer`='$data[emp]'";
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
        
        
        public function fetchPayRoll($data){
            
            if(!isset($data['month']) || !isset($data['year']) || !isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            $device=trim($data['device']);
            $month=trim($data['month']);
            $year=trim($data['year']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND `Employer`='$data[emp]'";
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
        
        
        
        public function addAdvance($data){
            
            if(!isset($data['account']) || !isset($data['usermobile']) || !isset($data['amount']) || !isset($data['startdate'])){
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
            
            // $username=trim($data['username']);
            if(isset($data['account'])){
                $account=trim($data['account']);
            }
            else{
                $account="NA";
            }
    
            if(isset($data['entrydate'])){
                $entrydate=trim($data['entrydate']);
            }
            else{
                $entrydate=$CurrDate;
            }
            
            $usermobile=trim($data['usermobile']);
            $amount=trim($data['amount']);
            $emi=trim($data['emi']);
            $startdate=trim($data['startdate']);
            $emp=trim($data['emp']);
    
    
    
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
                $controller = new ExpenseController($this->conn);
                // process_uploadBill($emp,$usermobile,$username,$type,$item,$shopid,$shop,$site,"","",0,$billno,$billamount,$billdate,$file,$fileavailable);
                $status = $controller->process_uploadBill($emp,$usermobile,$username,"Salary Advance","",0,"","$account","","",0,"",$amount,$entrydate,"","false");
                
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
        
    
        
        public function getAdvanceDetails($data){
            
            if(!isset($data['usermobile'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            
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
        
        
        public function rolloutPaySlip($data){
            
            if(!isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE Permission='Admin' AND `Employer`='$data[emp]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $chkCount = $stm->rowCount();
            // echo $chkCount;
            if($chkCount!=0){
    
                
                //Reverse balance in advance tracker if any
                $query = "UPDATE `AdvanceTracker` AS at SET `Balance` = (SELECT `NewAdv`.`newBal` 
                        FROM (SELECT `AdvanceTracker`.`Mobile` AS `mob`,COALESCE(`AdvanceTracker`.`Balance`,0)+COALESCE(`PayRollTracker`.`Advance`,0) AS `newBal` FROM `AdvanceTracker` 
                                    LEFT JOIN `PayRollTracker`  ON `AdvanceTracker`.`Mobile`=`PayRollTracker`.`Mobile` 
                                    WHERE `PayRollTracker`.`Month` IN (SELECT `Month` FROM `PayRollTemplate`) AND `PayRollTracker`.`Year` IN (SELECT `Year` FROM `PayRollTemplate`) AND `PayRollTracker`.`Mobile` IN (SELECT `Mobile` FROM `PayRollTemplate`)
                            ) AS `NewAdv` 
                        WHERE at.Mobile=`NewAdv`.`mob`)";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                
                $query = "UPDATE `AdvanceTracker` SET `Status`='Pending' WHERE `Balance`>'0' AND `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp')";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                // return;
                
                
                $query = "DELETE FROM `PayRollTracker` WHERE `Mobile` IN (SELECT Mobile FROM `EmployeeInfo` WHERE `Employer`='$emp') AND `Month` IN (SELECT `Month` FROM `PayRollTemplate`) AND `Year` IN (SELECT `Year` FROM `PayRollTemplate`) AND `Mobile` IN (SELECT `Mobile` FROM `PayRollTemplate`)";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                
                $query = "INSERT INTO `PayRollTracker` SELECT * FROM `PayRollTemplate` WHERE `Employer`='$emp'";
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
        
        
        
        public function getPaySlip($data){
            
            if(!isset($data['usermobile']) || !isset($data['year']) || !isset($data['device']) || !isset($data['emp'])){
                return;
            }
            
            // $month=trim($data['month']);
            $year=trim($data['year']);
            $usermobile=trim($data['usermobile']);
            $device=trim($data['device']);
            $emp=trim($data['emp']);
    
            
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";
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
    
    
        public function getEmployerDetails($data){
            
            if(!isset($data['emp'])){
                return;
            }
            
            $emp=trim($data['emp']);
            
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

        



    }


?>