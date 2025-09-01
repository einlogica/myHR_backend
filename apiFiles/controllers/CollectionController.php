<?php

    class CollectionController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }



        public function getMaterialSummary($data){

            if(!isset($data['emp']) || !isset($data['usermobile'])){
                return;
            } 
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
    
            // date_default_timezone_set('Asia/Kolkata');
        
            // $Time=date('H:i');
            // $CurrDate=date('Ymd');
            $CurrDate=trim($data['date']);
    
    
            $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";
    
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount!=0){
    
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $per = $row['Permission'];
    
                if($per==='Admin'){
                    $query="SELECT billertracker.division,billertracker.type,count(collectiontracker.ShopID) AS Shops,sum(collectiontracker.dryweight) AS dryweight,sum(collectiontracker.clothweight) AS clothweight,sum(collectiontracker.amount) AS rejAmount FROM collectiontracker LEFT JOIN billertracker ON collectiontracker.ShopID=billertracker.ShopID WHERE Date ='$CurrDate' AND collectiontracker.Mobile in (SELECT `Mobile` FROM `userinfo` WHERE `Employer`='$emp') GROUP BY billertracker.type, billertracker.division ORDER BY billertracker.division";
                }
                else{
                    $query="SELECT billertracker.division,billertracker.type,count(collectiontracker.ShopID) AS Shops,sum(collectiontracker.dryweight) AS dryweight,sum(collectiontracker.clothweight) AS clothweight,sum(collectiontracker.amount) AS rejAmount FROM collectiontracker LEFT JOIN billertracker ON collectiontracker.ShopID=billertracker.ShopID WHERE Date ='$CurrDate' AND collectiontracker.Mobile in (SELECT `Mobile` FROM userinfo WHERE `Employer`='$emp') GROUP BY billertracker.type, billertracker.division ORDER BY billertracker.division";
                }
                
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
            else{
                return json_encode(array("Message"=>"Failed"));
            }     
        }
    
        //"SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM u144195158_jilarihr.projectsites LEFT JOIN u144195158_jilarihr.activity ON projectsites.Type=activity.Site LEFT JOIN u144195158_jilarihr.billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='2024-08-21' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='2024-08-21' AND activity.Mobile in (SELECT Mobile FROM u144195158_jilarihr.userinfo WHERE Employer='WKerala') GROUP BY Site"
    
        public function getBillerSummary($data){
    
            if(!isset($data['emp']) || !isset($data['usermobile'])){
                return;
            } 
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $CurrDate=trim($data['date']);
    
            // date_default_timezone_set('Asia/Kolkata');
        
            // $Time=date('H:i');
            // $CurrDate=date('Ymd');
    
            $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";
    
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount!=0){
    
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $per = $row['Permission'];
    
                if($per==='Admin'){
                    $query="SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM projectsites LEFT JOIN activity ON projectsites.Type=activity.Site LEFT JOIN billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='$CurrDate' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='$CurrDate' AND activity.Mobile in (SELECT Mobile FROM userinfo WHERE Employer='$emp') GROUP BY Site";
                }
                else{
                    $query="SELECT projectsites.Type,count(activity.Site) AS Visited,count(billertracker.ShopID) AS NewAddition FROM projectsites LEFT JOIN activity ON projectsites.Type=activity.Site LEFT JOIN billertracker ON projectsites.Type=billertracker.ShopID AND billertracker.CreateDate='$CurrDate' WHERE projectsites.Employer='WKerala' AND activity.Type='Visit' AND activity.Date ='$CurrDate' AND activity.Mobile in (SELECT Mobile FROM userinfo WHERE Employer='$emp') GROUP BY Site";
                }
                
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
            else{
                return json_encode(array("Message"=>"Failed"));
            }     
        }
    
    
        public function getCashSummary($data){
    
            if(!isset($data['emp']) || !isset($data['usermobile'])){
                return;
            } 
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            $CurrDate=trim($data['date']);
    
            // date_default_timezone_set('Asia/Kolkata');
        
            // $Time=date('H:i');
            // $CurrDate=date('Ymd');
    
            $query = "SELECT Permission FROM userinfo WHERE `Mobile`='$usermobile' and `Employer`='$emp'";
    
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount!=0){
    
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                $per = $row['Permission'];
    
                if($per==='Admin'){
                    $query="SELECT Item,Sum(Total) As Amount FROM collectiontracker WHERE `Mobile` in (SELECT `Mobile` FROM UserInfo WHERE `Employer`='$emp') AND Date='$CurrDate' AND Item!='Material' Group BY Item";
                }
                else{
                    $query="SELECT Item,Sum(Total) As Amount FROM collectiontracker WHERE `Mobile` in (SELECT `Mobile` FROM UserInfo WHERE `Employer`='$emp') AND Date='$CurrDate' AND Item!='Material' Group BY Item";
                }
                
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
            else{
                return json_encode(array("Message"=>"Failed"));
            }     
        }
    
    
    
        public function uploadCollection($data){
            
            if(!isset($data['emp']) || !isset($data['username']) || !isset($data['usermobile']) || !isset($data['date']) || !isset($data['shopid'])){
                return;
            } 
    
            // date_default_timezone_set('Asia/Kolkata');
            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`=(SELECT `Employer` FROM `userinfo` WHERE `Mobile`='$data[usermobile]')";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
        
            $Time=date('H:i');
            $CurrDate=date('Ymd');
    
            $usermobile=trim($data['usermobile']);
            $username=trim($data['username']);
            $shopid=trim($data['shopid']);
            
            $shopname1=trim($data['shopname']);
            $shopname=str_replace("'", "''", $shopname1);
    
            $date=trim($data['date']);
            $dry=trim($data['dry']);
            $cloth=trim($data['cloth']);
            $amt=trim($data['amt']);
            $file=trim($data['image']);
            $emp=trim($data['emp']);
            $lat=trim($data['lat']);
            $long=trim($data['long']);
            $item=trim($data['item']);

            $billno='';
            if(isset($data['billno'])){
                $billno=trim($data['billno']);
            }
            
    
            
            $dryPrice=0;
            if($item!='Yard'){
                $dryPrice=$dry*10;
            }
            $clothPrice=$cloth*25;
            $tot=$dryPrice+$clothPrice+$amt;
    
            if($item==='Cash'){
                $amt=0;
            }
            
    
    
            
            if($file!=""){
                
                $target_dir ="/var/private_files/Collection/$emp/";
            
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
    
                
                $filename = $shopid."-".$CurrDate.$Time.".jpeg";
                $target_file = $target_dir . $filename;
                $data = base64_decode($file);      
                
                  if (file_put_contents($target_file, $data)) {
                
                    $query = "INSERT INTO `collectiontracker` (`Mobile`,`Name`, `ShopID`,`ShopName`,`Date`,`Time`,`DryWeight`,`ClothWeight`,`DryPrice`,`ClothPrice`, `Amount`, `Filename`,`Lat`,`Long`,`Total`,`Item`,`BillNo`) VALUES ('$usermobile','$username','$shopid','$shopname','$date','$Time','$dry','$cloth','$dryPrice','$clothPrice','$amt','$filename','$lat','$long','$tot','$item','$billno')";
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
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
                $query = "INSERT INTO `collectiontracker` (`Mobile`,`Name`, `ShopID`,`ShopName`,`Date`,`Time`,`DryWeight`,`ClothWeight`,`DryPrice`,`ClothPrice`, `Amount`, `Filename`,`Lat`,`Long`,`Total`,`Item`,`BillNo`) VALUES ('$usermobile','$username','$shopid','$shopname','$date','$Time','$dry','$cloth','$dryPrice','$clothPrice','$amt','NONE','$lat','$long','$tot','$item','$billno')";
                // echo $query;
                $stm = $this->conn->prepare($query);
                if($stm->execute()===TRUE){
                    return json_encode(array("Status"=>"Success","Mess"=>"Data Inserted"));
                }
                else{
                    return json_encode(array("Status"=>"Failed","Mess"=>"Data Insert Failed"));
                }
            }
    
        }
    
        public function getCollection($data){
            if(!isset($data['usermobile']) || !isset($data['per']) || !isset($data['date']) || !isset($data['emp'])){
                return;
            } 
            
            $usermobile=trim($data['usermobile']);
            $per = trim($data['per']);
            $date=trim($data['date']);
            // $month = trim($data['month']);
            $emp = trim($data['emp']);
    
            if($per==="User"){
                $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND `Mobile` ='$usermobile'";
            }
            else if($per==="Manager"){
                $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND (`Mobile` ='$usermobile' OR `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE Employer='$emp' AND ManagerID in (SELECT EmployeeID FROM `EmployeeInfo` WHERE `Mobile`='$usermobile')))";
            }
            else if($per==="Admin"){
                $query = "SELECT * FROM `collectiontracker` LEFT JOIN `billertracker` ON `collectiontracker`.`ShopID`=`billertracker`.`ShopID` WHERE `Date`='$date' AND `Mobile` in (SELECT `Mobile` FROM `EmployeeInfo` WHERE Employer='$emp')";
            }
            // return $query;
            
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
        }
    
    
        public function deleteCollection($data){
            if(!isset($data['id']) || !isset($data['emp'])){
                return;
            } 
    
            $billid=trim($data['id']);
            $emp=trim($data['emp']);
            $usermobile=trim($data['usermobile']);
    
            $query = "SELECT * FROM `collectiontracker` WHERE ID = '$billid' AND $usermobile in (SELECT `Mobile` FROM `EmployeeInfo` WHERE `Employer` = '$emp')";
            
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            // echo $rowCount;
            
            if($rowCount!=0){
               $billStatus=$stm->fetch(PDO::FETCH_ASSOC); 
               $imageFile=$billStatus['Filename'];
               if(strlen($imageFile)>5){
                   $file_pointer = "/var/private_files/Collection/$emp/$imageFile";
                   if(file_exists($file_pointer)){
                        if (!unlink($file_pointer)) {
                            return "$file_pointer cannot be deleted due to an error";
                        }
                   }
                   $query = "DELETE FROM `collectiontracker` WHERE ID = '$billid'";
            
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        return "Collection has been removed";
                    }
                    else{
                        return "Failed to remove collection";
                    }
                   
               }
               else{
                    $query = "DELETE FROM `collectiontracker` WHERE ID = '$billid'";
            
                    $stm = $this->conn->prepare($query);
                    if($stm->execute()===TRUE){
                        return "Collection has been removed";
                    } 
                    else{
                        return "Failed to remove collection";
                    }
               }
                
            }
    
    
    
        }

        



    }


?>