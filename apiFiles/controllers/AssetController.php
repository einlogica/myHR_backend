<?php

    require_once '/var/private_files/controllers/ImageController.php';

    class AssetController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function parseasset($emp,$id){


            $query = "SELECT `Role` FROM `userinfo` WHERE `ID`='$id'";

            
        
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);

            // echo $row['Role'];

            if($row['Role']>=750 || $row['Role']==260){
                $query="SELECT `assetsinfo`.* FROM `assetsinfo` WHERE `assetsinfo`.`Employer` = '$emp' OR `assetsinfo`.`ChangeEmployer`='$emp'";
            }
            else if($row['Role']==700){
                $query="SELECT * FROM `assetsinfo` WHERE (`assetsinfo`.`Employer` = '$emp' OR `assetsinfo`.`ChangeEmployer` = '$emp')
                AND (`CurrentID` NOT IN (SELECT `ID` FROM `userinfo` WHERE `Role`>700)) AND (`ChangeID` NOT IN (SELECT `ID` FROM `userinfo` WHERE `Role`>700))
                AND (`CurrentID` IN (SELECT `UserID` FROM `accessinfo` WHERE `CustomerID` IN (SELECT `CustomerID` FROM `accessinfo` WHERE `UserID`='$id')) OR `ChangeID` IN (SELECT `UserID` FROM `accessinfo` WHERE `CustomerID` IN (SELECT `CustomerID` FROM `accessinfo` WHERE `UserID`='$id')))";
            }
            else{
                $query="SELECT `assetsinfo`.* FROM `assetsinfo`  
                WHERE ( `assetsinfo`.`Employer` = '$emp' AND `CurrentID`='$id')
                OR (`assetsinfo`.`ChangeEmployer` = '$emp' AND `ChangeID`='$id')";
            }

            

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount!=0){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }

        }

        public function parsehistory($emp,$assetid){


            
            $query="SELECT * FROM `assetshistory` WHERE `AssetsID`='$assetid' ORDER BY `HistoryID` DESC";
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount!=0){
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }

        }

        public function getasset(){

            // $data = json_decode($data, true);

            if(empty($_GET['Employer']) || empty($_GET['UserID'])){
                return "Invalid Inputs";
            }

            $emp=$_GET['Employer'];
            $id = $_GET['UserID'];
            $assetid = $_GET['AssetID'];
            $type = $_GET['Type'];

            if($type==='Assets'){
                return $this->parseasset($emp,$id);
            }
            else{
                return $this->parsehistory($emp,$assetid);
            }
            

        }


        public function addasset($data){
            
            $data = json_decode($data, true);

            if(empty($data['UserID']) || empty($data['Name']) || empty($data['Employer']) || empty($data['Condition'])){
                return "Invalid Inputs";
            }

            date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
          
            $query = "SELECT * FROM `userinfo` WHERE `ID`='$data[UserID]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount===1){
                $row = $stm->fetch(PDO::FETCH_ASSOC);

                $sql = "INSERT INTO `assetsinfo` (`Employer`,`Name`,`Type`,`Tag`,`CurrentID`,`CurrentName`,`Status`,`PurchaseDate`,`Value`,`EntryDate`,`Condition`,`Remarks`) 
                                    VALUES ('$data[Employer]','$data[Name]','$data[Type]','$data[Tag]','$row[ID]','$row[Name]','Received','$data[Date]','$data[Value]','$CurrDate','$data[Condition]','$data[Remark]')"; 

                // Execute the query
                $stmt = $this->conn->prepare($sql);
                if($stmt->execute()==false){
                    return "Failed to add asset";
                }
                else
                {
                    return $this->parseasset($data['Employer'],$data['UserID']);
                }
    
            }
            else{
                return "invalid user";
            }
            

        }

        public function patchasset($data){
            
            $data = json_decode($data, true);

            if(empty($data['UserID']) || empty($data['AssetID']) || empty($data['Employer'])){
                return "Invalid Inputs";
            }

            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
            // $userEmployer = $data['UserEmployer'];
          
            // $query = "SELECT * FROM `userinfo` WHERE `ID`='$data[UserID]'";
            // $stm = $this->conn->prepare($query);
            // $stm->execute();
            // $rowCount = $stm->rowCount();
            
            // if($rowCount===1){

                if($data['Status']==='Transfer'){
                    $sql = "UPDATE `assetsinfo` SET `Status`='Transfer',`ChangeID`='$data[NewUserID]',`ChangeName`='$data[NewUserName]',`EntryDate`='$CurrDate' WHERE `AssetsID`='$data[AssetID]'"; 
                }
            
                else if($data['Status']==='Cancelled'){

                    $sql = "UPDATE `assetsinfo` SET `Status`='Received',`ChangeID`='0',`ChangeName`='',`ChangeEmployer`='0',`EntryDate`='$CurrDate' 
                    WHERE `AssetsID`='$data[AssetID]'"; 
                
                }
                if($data['Status']==='Rejected'){
                    if($data['Condition']==='NA'){
                        $sql = "UPDATE `assetsinfo` SET `Status`='Rejected',`ChangeID`='0',`ChangeName`='',`EntryDate`='$CurrDate',`Remarks`='$data[Remark]' WHERE `AssetsID`='$data[AssetID]'"; 
                    }
                    else{
                        $sql = "UPDATE `assetsinfo` SET `Status`='Rejected',`ChangeID`='0',`ChangeName`='',`EntryDate`='$CurrDate',`Condition`='$data[Condition]',`Remarks`='$data[Remark]' WHERE `AssetsID`='$data[AssetID]'"; 
                    }
                    
                }

                

                // Execute the query
                $stmt = $this->conn->prepare($sql);
                if($stmt->execute()==false){
                    return "Failed to update asset";
                }
                else
                {
                    
                    $query = "INSERT INTO `assetshistory` (`AssetsID`,`Employer`,`Name`,`Type`,`Tag`,`CurrentID`,`CurrentName`,`PreviousID`,`PreviousEmployer`,`PreviousName`,`Status`,`PurchaseDate`,`Value`,`EntryDate`,`Condition`,`Remarks`,`ChangeID`,`ChangeName`,`ChangeEmployer`) 
                                    SELECT * FROM `assetsinfo` WHERE `AssetsID`='$data[AssetID]'";

                                 
                    $stm = $this->conn->prepare($query);
                    $stm->execute();
                    return $this->parseasset($data['Employer'],$data['UserID']);
                }
    
            
            

        }

        public function acceptasset(){
            
            $data=$_POST;
            $file=$_FILES;
            // $data = json_decode($data, true);

            if(empty($data['ID']) || empty($data['Employer'])){
                return "Invalid Inputs";
            }

            $status=$data['Status'];
            if($data['Status']==='Accept'){
                $status = 'Received';
            }
            else if($data['Status']==='Reject'){
                $status='Rejected';
            }

            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');

            $query = "SELECT * FROM `assetsinfo` WHERE `AssetsID`='$data[ID]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            
            if($status==='Transfer'){
                $sql = "UPDATE `assetsinfo` SET `Status`='$status',`ChangeID`='$data[UserID]',`ChangeName`='$data[UserName]',`ChangeEmployer`='$data[UserEmployer]',`EntryDate`='$CurrDate',`Condition`='$data[Condition]',`Remarks`='$data[Remark]' 
                WHERE `AssetsID`='$data[ID]'"; 

            }
            else{
                $sql = "UPDATE `assetsinfo` SET `Status`='$status',`ChangeID`='0',`ChangeName`='',`ChangeEmployer`='0',`CurrentID`='$row[ChangeID]',`CurrentName`='$row[ChangeName]',`Employer`='$row[ChangeEmployer]',`PreviousID`='$row[CurrentID]',`PreviousName`='$row[CurrentName]', `PreviousEmployer`='$row[Employer]',`EntryDate`='$CurrDate',`Condition`='$data[Condition]',`Remarks`='$data[Remark]' 
                WHERE `AssetsID`='$data[ID]'"; 

            }

            // echo $sql;
            
            // Execute the query
            $stmt = $this->conn->prepare($sql);
            if($stmt->execute()==false){
                return "Failed";
            }
            else
            {
                
                $query = "INSERT INTO `assetshistory` (`AssetsID`,`Employer`,`Name`,`Type`,`Tag`,`CurrentID`,`CurrentName`,`PreviousID`,`PreviousEmployer`,`PreviousName`,`Status`,`PurchaseDate`,`Value`,`EntryDate`,`Condition`,`Remarks`,`ChangeID`,`ChangeName`,`ChangeEmployer`) 
                                SELECT * FROM `assetsinfo` WHERE `AssetsID`='$data[ID]'";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                if(!empty($files)){
                    $this -> addimage($data,$file);
                }
                
                return "Success";
            }
    
            
        
        }


        private function addimage($data,$files){
           
            
            $targetDir = "/var/private_files/Uploads/Assets/";
            $emp= $data['Employer'];
            $assetId = $data['ID'] ?? null;

            if (!$assetId || !isset($files) || empty($files)) {
                exit;
            }

            $assetFolder = $targetDir . "/" . $emp . "/" . $assetId . "/";
            if (!file_exists($assetFolder)) {
                mkdir($assetFolder, 0755, true);
            }
            

            $existingImages = glob($assetFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            usort($existingImages, function ($a, $b) {
                return filemtime($a) - filemtime($b); // sort oldest to newest
            });

            $totalNewImages = count($files['image']['name']);
            $totalExisting = count($existingImages);
            $totalAfterUpload = $totalExisting + $totalNewImages;

            $maxAllowed = 5;
            $imagesToRemove = $totalAfterUpload - $maxAllowed;

            $deleted = [];
            $uploaded = [];
            $errors = [];

            // Delete oldest files if needed
            if ($imagesToRemove > 0) {
                for ($i = 0; $i < $imagesToRemove; $i++) {
                    if (isset($existingImages[$i]) && file_exists($existingImages[$i])) {
                        if (unlink($existingImages[$i])) {
                            $deleted[] = basename($existingImages[$i]);
                        }
                    }
                }
            }

            // Upload new files
            for ($i = 0; $i < $totalNewImages; $i++) {
                $tmpName = $files['image']['tmp_name'][$i];
                $fileName = basename($files['image']['name'][$i]);
                $targetFile = $assetFolder . $fileName;
                

                // Optional: add unique name or check for duplicates
                
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploaded[] = $fileName;
                    
                } else {
                    $errors[] = "Failed to upload $fileName.";
                
                }
            }

        }


        public function delasset($data){

            $data = json_decode($data, true);

            if(empty($data['UserID']) || empty($data['AssetID']) || empty($data['Employer'])){
                return "Invalid Inputs";
            }

            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');

            $query = "SELECT * FROM `assetsinfo` WHERE `Employer`='$data[Employer]' AND `AssetsID`='$data[AssetID]'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);

            $query = "DELETE FROM `assetsinfo` WHERE `Employer` = '$data[Employer]' AND `AssetsID`='$data[AssetID]'";
            $stm = $this->conn->prepare($query);
        
            if($stm->execute()===true){
                // $query = "INSERT INTO `assetshistory` (`AssetsID`,`Employer`,`Name`,`Type`,`Tag`,`CurrentID`,`CurrentName`,`PreviousID`,`PreviousEmployer`,`PreviousName`,`Status`,`PurchaseDate`,`Value`,`EntryDate`,`Condition`,`Remarks`,`ChangeID`,`ChangeName`,`ChangeEmployer`) 
                //                 SELECT * FROM `assetsinfo` WHERE `AssetsID`='$data[ID]'";
                $query = "INSERT INTO `assetshistory` (`AssetsID`,`Employer`,`Name`,`Type`,`Tag`,`CurrentID`,`CurrentName`,`PreviousID`,`PreviousEmployer`,`PreviousName`,`Status`,`PurchaseDate`,`Value`,`EntryDate`,`Condition`,`Remarks`) 
                                    VALUES ('$row[AssetsID]','$row[Employer]','$row[Name]','$row[Type]','$row[Tag]','$row[CurrentID]','$row[CurrentName]','$row[PreviousID]','$row[PreviousEmployer]','$row[PreviousName]','Deleted','$row[PurchaseDate]','$row[Value]','$CurrDate','$row[Condition]','$row[Remarks]')";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                return $this->parseasset($data['Employer'],$data['UserID']);
            }
            else{
                return "Failed to delete access";
            }
            


        }


    }


?>