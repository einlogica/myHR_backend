<?php

    class PolicyController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function getPolicy($data){
        
            if (isset($data['emp'])) {
                
                $emp=trim($data['emp']);
            
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
        
        public function uploadPolicy($data){
            
            if (isset($data["title"]) && isset($data['emp']) && isset($data['device'])) {
                
                $title = $data["title"];
                $device = $data["device"];
                $emp = $data["emp"];
                
                $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
                $stm = $this->conn->prepare($qry);
                $stm->execute();
                $tz = $stm->fetch(PDO::FETCH_ASSOC);
                $timezone = $tz['TimeZone'];
            
                date_default_timezone_set($timezone);
                // date_default_timezone_set('Asia/Kolkata');
        
                $Time=date('His');
                $CurrDate=date('Ymd');
                $fileName = $title."-".$CurrDate.$Time.".pdf";
                
                $query = "SELECT * FROM `UserInfo` WHERE `Permission`='Admin' AND `Tocken`='$device'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $chkCount = $stm->rowCount();
                // echo $chkCount;
                if($chkCount!=0){
                    $targetdir = "/var/private_files/PolicyDocuments/$emp/"; // Directory where you want to store the uploaded files
                    
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
        
        public function deletePolicy($data){
            
           if (!isset($data["emp"]) || !isset($data['id']) || !isset($data['device'])) {
                return;
            }  
            
            $device=trim($data['device']);
            $emp=trim($data['emp']);
            $id=trim($data['id']);
            
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
                    $file_pointer = "/var/private_files/PolicyDocuments/$emp/$pdfFile";
                    if(file_exists($file_pointer)){
                        if (!unlink($file_pointer)) {
                            return "$file_pointer cannot be deleted due to an error";
                        }
                    }
                    $query = "DELETE FROM `PolicyDocs` WHERE `ID`='$id'";
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        return "Success";
                    }
                    else{
                        return "Failed";
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

        



    }


?>