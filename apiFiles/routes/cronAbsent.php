<?php

require_once '/var/private_files/config/connect_db.php';
require_once '/var/private_files/controllers/CronController.php';

$controller = new CronController($db);

date_default_timezone_set('Asia/Kolkata');
$Time=date('H:i:s');
$CurrDate=date('Y-m-d');

// $absentTime = '23:40:00';
$absentTime = '23:30:00';

// $calculationTime = '00:30:00';
$alertTime = '10:30:00';
$alertTimeEnd = '11:00:00';

$checkinTime = '08:40:00';
$checkinTimeEnd = '09:15:00';
$checkoutTime = '20:40:00';
$checkoutTimeEnd = '21:15:00';


error_log("Current time: $Time");
// error_log("Absent Time: $absentTime");
// error_log("Alert Time Start: $alertTime");
// error_log("Alert Time End$alertTimeEnd");

if($Time>$absentTime){
    error_log("Calling mark Absent");
    $controller->markAbsent();
}
else if($Time<$alertTimeEnd && $Time>$alertTime){
    error_log("Calling sendAlert");
    $controller->sendAlert();
}
else if($Time<$checkinTimeEnd && $Time>$checkinTime){
    error_log("Calling send Checkin");
    $controller->sendCheckin();
}
else if($Time<$checkoutTimeEnd && $Time>$checkoutTime){
    error_log("Calling send Checkout");
    $controller->sendCheckout();
}
else{
    error_log("Nothing called");
}

?>
