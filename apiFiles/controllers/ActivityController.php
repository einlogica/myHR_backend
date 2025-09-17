<?php

    class ActivityController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        

        public function getActivity($data){
        
            if(!isset($data['usermobile']) || !isset($data['date']) || !isset($data['type']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $date=trim($data['date']);
            $type=trim($data['type']);
            $emp=trim($data['emp']);
            
            
            if($type==='EMP' || $type==='EMP-MAN'){
                $query = "SELECT * FROM `Activity` WHERE Mobile = '$usermobile' AND `Employer` = '$emp' AND ActivityDate='$date'  ORDER BY ID DESC";
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
                
                $query = "SELECT * FROM `Activity` WHERE `ActivityDate`='$date' AND `Employer` = '$emp' AND `Mobile` in (SELECT Mobile FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')) ORDER BY ID DESC";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            else if($type==='ALL'){
                // echo "$type";
                $query = "(SELECT * FROM `Activity` WHERE `Employer`='$emp' AND `ActivityDate`='$date' ORDER BY `ID` DESC) 
                UNION 
                SELECT '0' as `ID`, `Mobile` ,`Name`, 'Pending' as Type,'' as Site,'false' as Drive,'0' as StartKM,'0' as EndKM,'0.00' as PosLat,'0.00' as PosLong,'Pending' as Activity,'$date' as ActivityDate,'$date' as Date,'00:00' as Time,'' as Customer,'' as Remarks,`Employer` 
                FROM `EmployeeInfo` WHERE `Employer`='$emp' and `Status`='ACTIVE' and `Mobile` not in (SELECT `Mobile` FROM `Activity` WHERE `Employer`='$emp' AND `ActivityDate`='$date')";
                // echo "$query";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    $row = $stm->fetchall(PDO::FETCH_ASSOC);
                
                    return json_encode($row);
                }
            }
            
            
        }
        
        
         public function post_Activity($data){
             
            if(!isset($data['username']) || !isset($data['usermobile']) || !isset($data['activity']) || !isset($data['date'])){
                return;
            }
             
            $username=trim($data['username']);
            $usermobile=trim($data['usermobile']);
            $type=trim($data['type']);
            $site=trim($data['site']);
            $date=trim($data['date']);
            $drive=trim($data['drive']);
            $sKM=trim($data['sKM']);
            $eKM=trim($data['eKM']);
            $lat=trim($data['lat']);
            $lat=floor((float)$lat * 1e6) / 1e6;
            $long=trim($data['long']);
            $long=floor((float)$long * 1e6) / 1e6;
            $activity=trim($data['activity']);
            $cust=trim($data['cust']);
            $custno=trim($data['custno']);
            // $emp=trim($data['emp']);
            
            // echo "Hi";

            // $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $qry = "SELECT `TimeZone`,`Employer` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$usermobile')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
            $emp = $tz['Employer'];
        
            date_default_timezone_set($timezone);
        
            $Time=date('His');
            $CurrDate=date('Y-m-d');
            // echo $drive;
            // return;
            
            
            $query = "INSERT INTO `Activity` (`Mobile`, `Name`,`Type`, `Site`, `Drive`, `StartKM`, `EndKM`, `PosLat`, `PosLong`,`ActivityDate`, `Date`, `Time`,`Customer`,`Remarks`,`Employer`) VALUES ('$usermobile','$username','$type','$site','$drive','$sKM','$eKM','$lat','$long','$date','$CurrDate','$Time','$cust','$custno','$emp')";
            // return $query;
            $stm = $this->conn->prepare($query);

            if($stm->execute()===TRUE){

                $query = "SELECT * FROM `Attendance` WHERE `Employer`='$emp' AND `Mobile` = '$usermobile' AND Date = '$date' AND DATE = '$CurrDate'";
                
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $rowCount = $stm->rowCount();
    
                if($rowCount===0){

                    $sql = "SELECT `ID`, `Location`, `PosLat`, `PosLong`, `Range`,(6371 * acos(cos(radians($lat)) * cos(radians(`PosLat`)) * cos(radians(`PosLong`) - radians($long)) + sin(radians($lat)) * sin(radians(`PosLat`))))*1000 AS distance
                            FROM locations WHERE `Employer`='$emp' HAVING distance<=`Range` ORDER BY distance ASC LIMIT 1";
                    $stm = $this->conn->prepare($sql);
                    $stm->execute();
                    $rowCount = $stm->rowCount();
                    if($rowCount===1){
                        $dis = $stm->fetch(PDO::FETCH_ASSOC);
                        $place=$dis['Location'];
                    }
                    else{
                        $place='Remote';
                    }

                    
                    // $dis = $stm->fetch(PDO::FETCH_ASSOC);
                    // if($dis['distance']<=$dis['Range']){
                    //     $place=$dis['Location'];
                    // }


                    $query = "INSERT INTO `Attendance` (`Mobile`, `Name`, `PosLat`, `PosLong`,`Date`,`InTime`, `PosLat2`, `PosLong2`,`OutTime`,`Status`,`Location`,`Flag`,`Employer`) VALUES ('$usermobile','$username','$lat','$long','$CurrDate','$Time','0.00','0.00','00:00:00','Present','$place','false','$emp')";
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    // if($stm->execute()){
                    //     return json_encode(array("Status"=>"Attendance applied","Mess"=>"Checkin has been applied"));
                    // }
                    // else{
                    //     return json_encode(array("Status"=>"Failed","Mess"=>"Failed to apply attendance"));
                    // } 
                }

                return json_encode(array("Status"=>"Success"));
            }
            else{
                return json_encode(array("Status"=>"Failed"));
            }
            
        }
        
        public function delete_Activity($data){
            
            if(!isset($data['id']) || !isset($data['usermobile'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $id=trim($data['id']);
            
            $query = "DELETE FROM `Activity` WHERE `Mobile`='$usermobile' AND `ID`='$id'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()){
                return json_encode(array("Status"=>"Success"));
            }
            else{
                return json_encode(array("Status"=>"Failed"));
            }
            
        }
        
        
        public function get_DriveActivity($data){
            
            if(!isset($data['usermobile'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            
            $value=0;
            $query = "SELECT * FROM `Activity` WHERE Mobile = '$usermobile' AND `Employer`='$emp' AND `Drive`='true' AND (`StartKM`='$value' OR `EndKM`='$value') ORDER BY ID DESC";
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
        
        public function update_DriveActivity($data){
            
            if(!isset($data['id']) || !isset($data['sKM']) || !isset($data['eKM'])){
                return;
            }
            
            $id=trim($data['id']);
    
            $sKM=trim($data['sKM']);
            $eKM=trim($data['eKM']);
            
            
            $query = "UPDATE `Activity` SET `StartKM`='$sKM' , `EndKM` = '$eKM' WHERE `ID` = '$id'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                return json_encode(array("Status"=>"Success"));
            }
            else{
                return json_encode(array("Status"=>"Failed"));
            }
            
        }
        
        
        
        public function getActivityType($data){
            
           if(!isset($data['emp'])){
                return;
            }
            
            $emp =trim($data['emp']);
            
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
    
        public function getCustomerType($data){
            
            if(!isset($data['emp'])){
                 return;
             }
             
             $emp =trim($data['emp']);
             
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

        



    }


?>