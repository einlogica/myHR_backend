<?php

    class LocationController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function getDefLocation($data){
        
            if(!isset($data['emp'])){
                return;
            }
            
            $emp = trim($data['emp']);
            
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
    
        public function saveLocations($data){
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['location']) || !isset($data['lat']) || !isset($data['long']) || !isset($data['range'])){
                return;
            }
    
            $emp = trim($data['emp']);
            $usermobile = trim($data['usermobile']);
            $location = trim($data['location']);
            $lat = trim($data['lat']);
            $long = trim($data['long']);
            $range = trim($data['range']);
    
            $query = "SELECT * FROM `UserInfo` WHERE `Employer`='$emp' AND `Mobile`='$usermobile' AND `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
       
            if($rowCount!=0){
                $query = "INSERT INTO `Locations` (`Employer`,`Location`,`PosLat`,`PosLong`,`Range`) VALUES ('$emp','$location','$lat','$long','$range')";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===True){
                    return "Success";
                }
                else{
                    return "Failed";
                }
            }
            else{
                return "Failed";
            }
        }
    
        public function deleteLocations($data){
            if(!isset($data['emp']) || !isset($data['usermobile']) || !isset($data['id'])){
                return;
            }
    
            $emp = trim($data['emp']);
            $usermobile = trim($data['usermobile']);
            $id = trim($data['id']);
     
    
            $query = "SELECT * FROM `UserInfo` WHERE `Employer`='$emp' AND `Mobile`='$usermobile' AND `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
       
            if($rowCount!=0){
                $query = "DELETE FROM `Locations` WHERE ID='$id' AND `Employer`='$emp'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()===True){
                    return "Success";
                }
                else{
                    return "Failed";
                }
            }
            else{
                return "Failed";
            }
        }

        



    }


?>