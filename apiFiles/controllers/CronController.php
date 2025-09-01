<?php

    require_once '/var/private_files/controllers/EmailController.php';
    require_once '/var/private_files/controllers/FCMController.php';


    class CronController {
        
        private $conn;
    
        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        
        public function markAbsent(){
            
            error_log("Executing mark absent");

            date_default_timezone_set('Asia/Kolkata');
        
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');


            if($Time<'23:30:00'){
                // $query = "INSERT INTO `testtable` (`Date`,`Time`) VALUES ('$CurrDate','$Time')";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
                return;
            }
            // $CurrDate='2024-06-12';
    
            // $pre_date = date('Y-m-d', strtotime("-1 days"));
            $next_date = date('Y-m-d', strtotime("+1 days"));
            
            // echo $pre_date;
            
            $day = date('l',strtotime("$CurrDate"));
    
            $flag=0;
    
    
    
            //Mark offday for next day as per holiday table
            $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `Mobile`,`Name`,'0.00','0.00','$next_date','00:00:00','0.00','0.00','00:00:00','Holiday','Holiday' FROM `EmployeeInfo` WHERE `Employer` in (SELECT Employer FROM `holidaycalendar` WHERE `Date`='$next_date')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $affectedRows = $stm->rowCount();
                error_log("Holiday : $affectedRows");
                $flag=$flag+1;
            } 

            //Mark Holiday for current Day if SUNDAY
            $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$CurrDate','00:00:00','0.00','0.00','00:00:00',IF(`LeaveTracker`.`Status`='Approved','Leave','Holiday'),IF(`LeaveTracker`.`Status`='Approved','Leave','Holiday') FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$CurrDate' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `EmployeeInfo`.`Employer` IN (SELECT `Employer` FROM `Settings` WHERE FIND_IN_SET('$day', `WeekOff`) = 1) AND `EmployeeInfo`.`Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$CurrDate')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $affectedRows = $stm->rowCount();
                error_log("Offday : $affectedRows");
                $flag=$flag+1;
            } 
    
            //Mark Absent for current Day for those didnt apply
            // $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Location`) VALUES SELECT `Mobile`,`Name`,'0.00','0.00','$pre_date','00:00:00','0.00','0.00','00:00:00','Absent' FROM `EmployeeInfo` WHERE `Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$pre_date' )";
            $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$CurrDate','00:00:00','0.00','0.00','00:00:00',IF(`LeaveTracker`.`Status`='Approved','Leave','Absent'),IF(`LeaveTracker`.`Status`='Approved','Leave','Absent') FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$CurrDate' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `EmployeeInfo`.`Employer` IN (SELECT `Employer` FROM `Settings` WHERE FIND_IN_SET('$day', `WeekOff`) = 0) AND `EmployeeInfo`.`Mobile` NOT IN (SELECT `Mobile` FROM `Attendance` WHERE `Date` ='$CurrDate')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $affectedRows = $stm->rowCount();
                error_log("Absent : $affectedRows");
            
                $flag=$flag+1;
            }    

    
            //Mark Leave for those who applied leave
            $query = "INSERT INTO `Attendance` (`Mobile`,`Name`,`PosLat`,`PosLong`,`Date`,`InTime`,`PosLat2`,`PosLong2`,`OutTime`,`Status`,`Location`) SELECT `EmployeeInfo`.`Mobile`,`EmployeeInfo`.`Name`,'0.00','0.00','$next_date','00:00:00','0.00','0.00','00:00:00','Leave','Leave' FROM `EmployeeInfo` LEFT JOIN `LeaveTracker` ON `EmployeeInfo`.`Mobile`=`LeaveTracker`.`Mobile` AND `LeaveTracker`.`LeaveDate`='$next_date' WHERE `EmployeeInfo`.`Status`='ACTIVE' AND `LeaveTracker`.`Status`='Approved'";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $affectedRows = $stm->rowCount();
                error_log("Leave : $affectedRows");
                $flag=$flag+1;
            }
            
            //Mark Deactiviation of account once expired
            $query = "UPDATE `settings` SET `Status`=0 WHERE `Status`!=0 AND `Employer` IN (SELECT `Employer` FROM `subscriptions` WHERE `Expiry` < DATE_SUB(CURDATE(), INTERVAL 5 DAY))";
            $stm = $this->conn->prepare($query);
            if($stm->execute()===TRUE){
                $affectedRows = $stm->rowCount();
                error_log("Deactivation : $affectedRows");
                $flag=$flag+1;
            }
    
    
            if($flag>0){
            error_log( "Done -".$flag."-".$CurrDate."-".$Time);
            }
                else{
            error_log("Error -".$CurrDate."-".$Time);
            }
    
            
        }


        //  public function sendAlert(){

        //     //Send Notification before expiry
        //     $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
        //     LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
        //     LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Mobile`='9747050500'";
            

        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $rowCount = $stm->rowCount();
        //     $rows = $stm->fetchall(PDO::FETCH_ASSOC);
        //     if($rowCount>0){

                

        //         $controller2 = new EmailController();
        //         foreach ($rows as $row) {
        //             if (!empty($row['Email'])) {
        //                 $controller2->sendExpiry($row['Email'],$row['Name'],'Alert');
        //             }
        //         }
                

        //         $controller = new FCMController($this->conn);
        //         foreach ($rows as $row) {
        //             $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account is nearing expiry. Renew now to avoid service interruption.');
        //         }

                
                

                

        //         // print_r($emails);
        //         // error_log($emails[0]);

                

        //         echo "Notification send";

        //     }

        //  }



        public function sendAlert(){
    
            error_log("Sending Notification");

            //Send Notification before expiry - before 5 and 1 day
            $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
            LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
            LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND (`subscriptions`.`Expiry` = DATE_ADD(CURDATE(), INTERVAL 5 DAY) OR `subscriptions`.`Expiry` = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
            

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            $rows = $stm->fetchall(PDO::FETCH_ASSOC);
            if($rowCount>0){


                $controller2 = new EmailController();
                foreach ($rows as $row) {
                    if (!empty($row['Email'])) {
                        $controller2->sendExpiry($row['Email'],$row['Name'],'Alert');
                    }
                }

                $controller = new FCMController($this->conn);
                foreach ($rows as $row) {
                    $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account is nearing expiry. Renew now to avoid service interruption.');
                }

                echo "Notification send";
                error_log("Expiry reminder send to $rowCount");

            }




            //Send Warning before expiry - after 1 and 3 days
            $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
            LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
            LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND (`subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 1 DAY) OR `subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 3 DAY))";
            

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            $rows = $stm->fetchall(PDO::FETCH_ASSOC);
            if($rowCount>0){

                $controller2 = new EmailController();
                foreach ($rows as $row) {
                    if (!empty($row['Email'])) {
                        $controller2->sendExpiry($row['Email'],$row['Name'],'Warning');
                    }
                }

                $controller = new FCMController($this->conn);
                foreach ($rows as $row) {
                    $controller->send_fcm_message($row['fcmtocken'],'Renew your Subscription','Reminder! Your account has been expired. Renew now to avoid service interruption.');
                }

                echo "Notification send";
                error_log("Expired notification send to $rowCount");

            }



            //Send Locking notification - on 8th day

            $query = "UPDATE `settings` SET `Status`=0 WHERE `Employer` IN (SELECT `Employer` FROM `subscriptions` WHERE `Expiry` = DATE_SUB(CURDATE(), INTERVAL 8 DAY))";
            $stm = $this->conn->prepare($query);
            $stm->execute();

            $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
            LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
            LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND `subscriptions`.`Expiry` = DATE_SUB(CURDATE(), INTERVAL 8 DAY)";
            

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            $rows = $stm->fetchall(PDO::FETCH_ASSOC);
            if($rowCount>0){

                $controller2 = new EmailController();
                foreach ($rows as $row) {
                    if (!empty($row['Email'])) {
                        $controller2->sendExpiry($row['Email'],$row['Name'],'Lock');
                    }
                }

                $controller = new FCMController($this->conn);
                foreach ($rows as $row) {
                    $controller->send_fcm_message($row['fcmtocken'],'Subscription Expired','Your account has been locked. Renew now to gain full access.');
                }

                echo "Notification send";
                error_log("Locked notification send to $rowCount");

            }

        }

        public function sendCheckin(){

            // $query = "SELECT `userinfo`.`Name`,`userinfo`.`Email`,`subscriptions`.`Expiry`,`fcmtocken`.`fcmtocken` FROM `userinfo` 
            // LEFT JOIN `subscriptions` ON `userinfo`.`Employer`=`subscriptions`.`Employer` 
            // LEFT JOIN `fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` WHERE `userinfo`.`Permission`='Admin' AND (`subscriptions`.`Expiry` = DATE_ADD(CURDATE(), INTERVAL 5 DAY) OR `subscriptions`.`Expiry` = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
            
            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
            $day = date('l',strtotime("$CurrDate"));
            
            $query = "SELECT `userinfo`.`Name`,`userinfo`.`Employer`,`fcmtocken`.`fcmtocken`,`settings`.`WeekOff` FROM u144195158_jilarihr.`userinfo`
            LEFT JOIN  u144195158_jilarihr.`settings` ON `userinfo`.`Employer`=`settings`.`Employer`
            LEFT JOIN u144195158_jilarihr.`fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` 
            WHERE `userinfo`.`Mobile` NOT IN (SELECT `Mobile` FROM u144195158_jilarihr.`attendance` WHERE `Date`='$CurrDate') 
            AND FIND_IN_SET('$day', `settings`.`WeekOff`) = 0
            AND `fcmtocken`.`fcmtocken` IS NOT NULL";

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            $rows = $stm->fetchall(PDO::FETCH_ASSOC);
            if($rowCount>0){



                $controller = new FCMController($this->conn);
                foreach ($rows as $row) {
                    $controller->send_fcm_message($row['fcmtocken'],'Check-in Reminder','Forget to check-in on MyHR! Please complete your check-in now');
                }

                echo "Notification send";
                error_log("Checkin reminder send to $rowCount");

            }

        }

        public function sendCheckout(){

            date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
            $day = date('l',strtotime("$CurrDate"));
            
            $query = "SELECT `userinfo`.`Name`,`userinfo`.`Employer`,`fcmtocken`.`fcmtocken`,`settings`.`WeekOff` FROM u144195158_jilarihr.`userinfo`
            LEFT JOIN  u144195158_jilarihr.`settings` ON `userinfo`.`Employer`=`settings`.`Employer`
            LEFT JOIN u144195158_jilarihr.`fcmtocken` ON `userinfo`.`Mobile`=`fcmtocken`.`Mobile` 
            WHERE `userinfo`.`Mobile` IN (SELECT `Mobile` FROM u144195158_jilarihr.`attendance` WHERE `Date`='$CurrDate' AND `Status`='Present' AND `OutTime`='00:00:00') 
            AND `fcmtocken`.`fcmtocken` IS NOT NULL";

            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            $rows = $stm->fetchall(PDO::FETCH_ASSOC);
            if($rowCount>0){



                $controller = new FCMController($this->conn);
                foreach ($rows as $row) {
                    $controller->send_fcm_message($row['fcmtocken'],'Check-out Reminder','Reminder to applay check-out on MyHR');
                }

                echo "Notification send";
                error_log("Checkout reminder send to $rowCount");

            }


        }



    }
?>
