<?php

    class HolidayController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }


        public function postHoliday($data){
        
            if(!isset($data['emp']) || !isset($data['date']) || !isset($data['name']) || !isset($data['device'])){
                return;
            }
            
            $emp=trim($data['emp']);
            $date=trim($data['date']);
            $name=trim($data['name']);
            $device=trim($data['device']);
            
            
            
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
        
        public function getHoliday($data){
            if(!isset($data['emp'])){
                return;
            }

            $emp=trim($data['emp']);
            
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
            // date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('His');
            $CurrDate=date('Ymd');
            
            
            
            
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
        
        
        public function deleteHoliday($data){
            
            if(!isset($data['emp']) || !isset($data['id']) || !isset($data['device'])){
                return;
            }
            
            $emp=trim($data['emp']);
            $id=trim($data['id']);
            $device=trim($data['device']);
            
            
            
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
        
        public function getEvents($data){
            
            if(!isset($data['emp'])){
                return;
            }
            
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

        



    }


?>