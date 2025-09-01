<?php

    class SettingsController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function fetchSettingsList($data){
            if (!isset($data["usermobile"]) || !isset($data['emp']) || !isset($data['selection'])) {
                return;
            }
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $selection=trim($data['selection']);
            
    
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount=$stm->rowCount();
    
            if($rowCount!=0){
    
                if($selection==='Activity'){
                    $selection='activitytype';
                }
                else if($selection==='Customer'){
                    $selection='customertype';
                }
                else if($selection==='Vehicle'){
                    $selection='vehicledata';
                }
                else if($selection==='Department'){
                    $selection='departments';
                }
                else if($selection==='Position'){
                    $selection='positions';
                }
                else if($selection==='Site Name'){
                    $selection='projectsites';
                }
                else if($selection==='Bank'){
                    $selection='accounts';
                }
                else if($selection==='Expense'){
                    $selection='expensetype';
                }
    
                $query = "SELECT * FROM `$selection` WHERE `Employer`='$emp' ORDER BY `Type` asc";
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
    
        public function updateSettingsList($data){
    
          
            if (!isset($data["usermobile"]) || !isset($data['emp']) || !isset($data['list'])) {
                return "Invalid";
            }
           
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $list=trim($data['list']);
            $selection=trim($data['selection']);
    
            $bulkUsers = json_decode($list);
            // if (empty($bulkUsers) || $bulkUsers === null) {
            //     $listValues = array();
            // } else {
            //     $listValues = "'".implode("','", $bulkUsers)."'";
            // }
            $listValues = "'".implode("','", $bulkUsers)."'";
    
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' AND `Employer`='$emp' AND `Permission`!='User'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount=$stm->rowCount();
    
            if($rowCount!=0){
    
                if($selection==='Activity'){
                    $selection='activitytype';
                }
                else if($selection==='Customer'){
                    $selection='customertype';
                }
                else if($selection==='Vehicle'){
                    $selection='vehicledata';
                }
                else if($selection==='Department'){
                    $selection='departments';
                }
                else if($selection==='Position'){
                    $selection='positions';
                }
                else if($selection==='Site Name'){
                    $selection='projectsites';
                }
                else if($selection==='Bank'){
                    $selection='accounts';
                }
                else if($selection==='Expense'){
                    $selection='expensetype';
                }
                
                $query = "Delete FROM `$selection` WHERE `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
    
                if (empty($bulkUsers) || $bulkUsers === null) {
                    return "Success";
                }
    
                $query = "INSERT INTO `$selection` (`Employer`,`Type`) VALUES ";
                
                foreach ($bulkUsers as $item){
                    $value[] =  "('$emp','$item')";
                }
               
                $values_clause_string = implode(", ", $value);
    
                $query = $query . $values_clause_string ;
    
                // return $query;
    
    
                $stm = $this->conn->prepare($query);
    
                if($stm->execute()===TRUE){
                    return "Success";
                }
                else{
                    return "Failed";
                }
                
                
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
    
            }
    
    
        }

        



    }


?>