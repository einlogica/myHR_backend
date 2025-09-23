<?php

    class ImageController{


        // private $conn;

        // public function __construct($db){
        //     $this->conn=$db;
        // }

        // public function __destruct(){
        //     if($this->conn){
        //         $this->conn=null;
        //     }
        // }

        public function getimage(){

            // $data = json_decode($data, true);
            

            if(empty($_GET['Employer']) || empty($_GET['ID']) || empty($_GET['Type'])){
                return "Invalid Inputs";
            }

            $emp=$_GET['Employer'];
            $id = $_GET['ID'];
            $type = $_GET['Type'];

            if($type==='Assets'){
                $folder = "/var/private_files/Uploads/Assets/" . $emp . "/" . $id;
            }
            else if($type==="Expenses"){
                $folder = "/var/private_files/Uploads/Expenses/" . $emp . "/" . $id;
            }

            
            $result = [];
            $images = glob($folder . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            foreach ($images as $path) {
                $filename = basename($path);
                $mime = mime_content_type($path);
                $base64 = base64_encode(file_get_contents($path));

                $result[] = [
                    "filename" => $filename,
                    "mime" => $mime,
                    "base64" => "data:$mime;base64,$base64"
                ];
                
            }
            

            header("Content-Type: application/json");
            echo json_encode($result);

            // if (file_exists($folder)) {
            //     foreach (glob($folder . "/*.{jpg,jpeg,png,gif}", GLOB_BRACE) as $file) {
            //         $name = basename($file);
            //         $result[] = [
            //             "name" => $name,
            //             "url" => $id . "/" . $name
            //         ];
            //     }
            // }

            // echo json_encode($result);

        }


        public function addimage($data,$files){
           
            // $data = json_decode($data, true);

            $id = $data['ID'];
            $type = $data['Type'];

            if($type==='Assets'){
                $targetDir = "/var/private_files/Uploads/Assets/";
                $maxAllowed = 5;
            }
            else{
                $targetDir = "/var/private_files/Uploads/Profile/";
                $maxAllowed = 500;
            }
            
            if (!$id || !isset($files)) {
                http_response_code(400);
                echo "Missing asset_id or file.";
                exit;
            }

            $idFolder = $targetDir . $id . "/";
            if (!file_exists($idFolder)) {
                mkdir($idFolder, 0755, true);
            }
            

            $existingImages = glob($idFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            usort($existingImages, function ($a, $b) {
                return filemtime($a) - filemtime($b); // sort oldest to newest
            });

            $totalNewImages = count($files['image']['name']);
            $totalExisting = count($existingImages);
            $totalAfterUpload = $totalExisting + $totalNewImages;

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
                $targetFile = $idFolder . $fileName;
                

                // Optional: add unique name or check for duplicates
                // echo "uploading file";
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploaded[] = $fileName;
                    echo "image uploaded";
                } else {
                    $errors[] = "Failed to upload $fileName.";
                    echo "upload failed";
                }
            }

        }

        

        public function deleteaccess($data){

            $data = json_decode($data, true);

            if(empty($data['UserID']) || empty($data['CustomerID']) || empty($data['Employer'])){
                return "Invalid Inputs";
            }


            $query = "DELETE FROM `accessinfo` WHERE `Employer` = '$data[Employer]' AND `UserID`='$data[UserID]' AND `CustomerID`='$data[CustomerID]'";
            $stm = $this->conn->prepare($query);
        
            if($stm->execute()){
                return "Access Deleted";
            }
            else{
                return "Failed to delete access";
            }
            


        }


    }


?>