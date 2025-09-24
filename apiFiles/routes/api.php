<?php
// routes/api.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

require_once '/var/private_files/config/jwt.php';
require_once '/var/private_files/config/connect_db.php';

require_once '/var/private_files/controllers/AuthController.php';
require_once '/var/private_files/controllers/AttendanceController.php';
require_once '/var/private_files/controllers/UserController.php';
require_once '/var/private_files/controllers/LeaveController.php';
require_once '/var/private_files/controllers/CollectionController.php';
require_once '/var/private_files/controllers/PaymentController.php';
require_once '/var/private_files/controllers/PayslipController.php';
require_once '/var/private_files/controllers/HolidayController.php';
require_once '/var/private_files/controllers/ActivityController.php';
require_once '/var/private_files/controllers/SettingsController.php';
require_once '/var/private_files/controllers/PolicyController.php';
require_once '/var/private_files/controllers/LocationController.php';
require_once '/var/private_files/controllers/ExpenseController.php';
require_once '/var/private_files/controllers/FCMController.php';
require_once '/var/private_files/controllers/ExtraController.php';
require_once '/var/private_files/controllers/AssetController.php';

$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$resource = $requestUri[1] ?? '';
$action = $requestUri[2] ?? '';
// $action = $_SERVER['REQUEST_METHOD'];


// echo $requestUri[2];
// echo $_SERVER['REQUEST_URI'];
// echo $resource;
// echo $action;

$data = json_decode(file_get_contents('php://input'),true);

if ($resource === 'login') {
    $controller = new AuthController($db);
    
    if($action == 'login'){
        echo $controller->login_fun($data);
    }
    else if($action == 'register'){
        echo $controller->register($data);
    }
    // else if($action == 'sendAlert'){
    //     echo $controller->sendAlert();
    // }
    // else if($action == 'markAbsent'){
    //     echo $controller->markAbsent();
    // }
    
    else if($action == 'payment'){
        echo $controller->payment($data);
    }
}

elseif(decodeJWT()!=''){

    //localhost:8081/index.php/users
    // within Body, set "raw" and add json
    

    switch ($resource){


        case 'asset':
                $controller = new AssetController($db);
                if ($action === 'get_assets') {
                    echo $controller->get_assets();
                } 
                elseif ($action === 'add_asset') {
                    $resource2 = $requestUri[3] ?? '';
                
                    if($resource2==='accept'){
                        echo $controller->acceptasset();
                    }
                    else{
                        echo $controller->addasset($data);
                    }
                }
                elseif ($action === 'delete_asset') {
                    echo $controller->delasset($data);
                }
                elseif ($action === 'update_asset') {
                    echo $controller->patchasset($data);
                }
                break; 
        case 'attendance':
            $controller = new AttendanceController($db);
            if($action == 'getMonthlyAttendance'){
                echo $controller->getMonthlyAttendance($data);
            }
            else if($action == 'get_attendanceStatus'){
                echo $controller->get_attendanceStatus($data);
            }
            else if($action == 'markAbsent'){
                echo $controller->markAbsent($data);
            }
            else if($action == 'post_Attendance'){
                echo $controller->post_Attendance($data);
            }
            else if($action == 'get_AttendanceData'){
                echo $controller->get_AttendanceData($data);
            }
            else if($action == 'getRegularization'){
                echo $controller->getRegularization($data);
            }
            else if($action == 'postRegularization'){
                echo $controller->postRegularization($data);
            }
            else if($action == 'approveRegularization'){
                echo $controller->approveRegularization($data);
            }
            else if($action == 'postRegularization'){
                echo $controller->postRegularization($data);
            }
            else if($action == 'postRegularization'){
                echo $controller->postRegularization($data);
            }
            break;

        case 'users':
            $controller = new UserController($db);
            if($action == 'register'){
                echo $controller->register($data);
            }
            else if($action == 'upload_image'){
                echo $controller->upload_image($data);
            }
            else if($action == 'upload_logo'){
                echo $controller->upload_logo($data);
            }
            else if($action == 'get_userdetails'){
                echo $controller->get_userdetails($data);
            }
            else if($action == 'getProfile'){
                echo $controller->getProfile($data);
            }
            else if($action == 'getReportees'){
                echo $controller->getReportees($data);
            }
            else if($action == 'add_user'){
                echo $controller->add_user($data);
            }
            else if($action == 'updatePersonalInfo'){
                echo $controller->updatePersonalInfo($data);
            }
            else if($action == 'resetPassword'){
                echo $controller->resetPassword($data);
            }
            else if($action == 'change_password'){
                echo $controller->change_password($data);
            }
            else if($action == 'searchDirectory'){
                echo $controller->searchDirectory($data);
            }
            else if($action == 'updateBasicDetails'){
                echo $controller->updateBasicDetails($data);
            }
            else if($action == 'checkPersonalInfo'){
                echo $controller->checkPersonalInfo($data);
            }
            else if($action == 'deactivateEmployee'){
                echo $controller->deactivateEmployee($data);
            }
            else if($action == 'activateEmployee'){
                echo $controller->activateEmployee($data);
            }
            else if($action == 'getDepartments'){
                echo $controller->getDepartments($data);
            }
            else if($action == 'getPositions'){
                echo $controller->getPositions($data);
            }
            break;  
        case 'leave':
            $controller = new LeaveController($db);
            if($action == 'getLeave'){
                echo $controller->getLeave($data);
            }
            elseif($action == 'deleteLeave'){
                echo $controller->deleteLeave($data);
            }
            elseif($action == 'postLeave'){
                echo $controller->postLeave($data);
            }
            elseif($action == 'updateLeave'){
                echo $controller->updateLeave($data);
            }
            break; 
        case 'collection':
            $controller = new CollectionController($db);
            if($action == 'getMaterialSummary'){
                echo $controller->getMaterialSummary($data);
            }
            elseif($action == 'getBillerSummary'){
                echo $controller->getBillerSummary($data);
            }
            elseif($action == 'getCashSummary'){
                echo $controller->getCashSummary($data);
            }
            elseif($action == 'uploadCollection'){
                echo $controller->uploadCollection($data);
            }
            elseif($action == 'getCollection'){
                echo $controller->getCollection($data);
            }
            elseif($action == 'deleteCollection'){
                echo $controller->deleteCollection($data);
            }
            break; 
        case 'payment':
            $controller = new PaymentController($db);
            if($action == 'getSubscription'){
                echo $controller->getSubscription($data);
            }
            elseif($action == 'updatePayment'){
                echo $controller->updatePayment($data);
            }
            elseif($action == 'getOrderid'){
                echo $controller->getOrderid($data);
            }
            elseif($action == 'validateSignature'){
                echo $controller->validateSignature($data);
            }
            elseif($action == 'getPaymentList'){
                echo $controller->getPaymentList($data);
            }
            break;    
        case 'payslip':
            $controller = new PayslipController($db);
            if($action == 'getSalaryStructure'){
                echo $controller->getSalaryStructure($data);
            }
            elseif($action == 'updateSalary'){
                echo $controller->updateSalary($data);
            }
            elseif($action == 'getAdvanceDetails'){
                echo $controller->getAdvanceDetails($data);
            }
            elseif($action == 'addAdvance'){
                echo $controller->addAdvance($data);
            }
            elseif($action == 'getPayrollTemplate'){
                echo $controller->getPayrollTemplate($data);
            }
            elseif($action == 'fetchPayRoll'){
                echo $controller->fetchPayRoll($data);
            }
            elseif($action == 'generatePayroll'){
                echo $controller->generatePayroll($data);
            }
            elseif($action == 'deleteTemplate'){
                echo $controller->deleteTemplate($data);
            }
            elseif($action == 'importPayRoll'){
                echo $controller->importPayRoll($data);
            }
            elseif($action == 'rolloutPaySlip'){
                echo $controller->rolloutPaySlip($data);
            }
            elseif($action == 'getPaySlip'){
                echo $controller->getPaySlip($data);
            }
            elseif($action == 'getEmployerDetails'){
                echo $controller->getEmployerDetails($data);
            }
            break; 
        case 'activity':
            $controller = new ActivityController($db);
            if($action == 'update_DriveActivity'){
                echo $controller->update_DriveActivity($data);
            }
            elseif($action == 'getActivity'){
                echo $controller->getActivity($data);
            }
            elseif($action == 'delete_Activity'){
                echo $controller->delete_Activity($data);
            }
            elseif($action == 'post_Activity'){
                echo $controller->post_Activity($data);
            }
            elseif($action == 'getActivityType'){
                echo $controller->getActivityType($data);
            }
            elseif($action == 'getCustomerType'){
                echo $controller->getCustomerType($data);
            }
            break;
            
        case 'holiday':
            $controller = new HolidayController($db);
            if($action == 'getHoliday'){
                echo $controller->getHoliday($data);
            }
            elseif($action == 'postHoliday'){
                echo $controller->postHoliday($data);
            }
            elseif($action == 'deleteHoliday'){
                echo $controller->deleteHoliday($data);
            }
            elseif($action == 'getEvents'){
                echo $controller->getEvents($data);
            }
            break;    
        case 'settings':
            $controller = new SettingsController($db);
            if($action == 'fetchSettingsList'){
                echo $controller->fetchSettingsList($data);
            }
            elseif($action == 'updateSettingsList'){
                echo $controller->updateSettingsList($data);
            }
            
            break;       
        case 'policy':
            $controller = new PolicyController($db);
            if($action == 'getPolicy'){
                echo $controller->getPolicy($data);
            }
            elseif($action == 'uploadPolicy'){
                echo $controller->uploadPolicy($data);
            }
            elseif($action == 'deletePolicy'){
                echo $controller->deletePolicy($data);
            }
            break; 
        case 'location':
            $controller = new LocationController($db);
            if($action == 'getDefLocation'){
                echo $controller->getDefLocation($data);
            }
            elseif($action == 'saveLocations'){
                echo $controller->saveLocations($data);
            }
            elseif($action == 'deleteLocations'){
                echo $controller->deleteLocations($data);
            }
            break;                    
        case 'expense':
            $controller = new ExpenseController($db);
            if($action == 'getExpenseType'){
                echo $controller->getExpenseType($data);
            }
            elseif($action == 'getMonthlyExpense'){
                echo $controller->getMonthlyExpense($data);
            }
            elseif($action == 'get_userexpense'){
                echo $controller->get_userexpense($data);
            }
            elseif($action == 'get_preexpense'){
                echo $controller->get_preexpense($data);
            }
            elseif($action == 'getVehicle'){
                echo $controller->getVehicle($data);
            }
            elseif($action == 'get_billImage'){
                echo $controller->get_billImage($data);
            }
            elseif($action == 'delete_bill'){
                echo $controller->delete_bill($data);
            }
            elseif($action == 'updateExpense'){
                echo $controller->updateExpense($data);
            }
            elseif($action == 'clearExpense'){
                echo $controller->clearExpense($data);
            }
            elseif($action == 'addEmployeeAdvance'){
                echo $controller->addEmployeeAdvance($data);
            }
            elseif($action == 'addDailyWages'){
                echo $controller->addDailyWages($data);
            }
            elseif($action == 'upload_bill'){
                echo $controller->upload_bill($data);
            }
            elseif($action == 'upload_purchasebill'){
                echo $controller->upload_purchasebill($data);
            }
            elseif($action == 'getBiller'){
                echo $controller->getBiller($data);
            }
            elseif($action == 'addBiller'){
                echo $controller->addBiller($data);
            }
            break;         
        case 'fcm':
            $controller = new FCMController($db);
            if($action == 'updatefcm'){
                echo $controller->updatefcm($data);
            }
            break;
            
           
        case 'extra':
            $controller = new ExtraController($db);
            if($action == 'getPendingActions'){
                echo $controller->getPendingActions($data);
            }
            elseif($action == 'getDistrict'){
                echo $controller->getDistrict($data);
            }
            elseif($action == 'getQuote'){
                echo $controller->getQuote($data);
            }
            elseif($action == 'getSettings'){
                echo $controller->getSettings($data);
            }
            elseif($action == 'getAllSettings'){
                echo $controller->getAllSettings($data);
            }
            elseif($action == 'getAccounts'){
                echo $controller->getAccounts($data);
            }
            elseif($action == 'getDashboardSummary'){
                echo $controller->getDashboardSummary($data);
            }
            elseif($action == 'get_tracker'){
                echo $controller->get_tracker($data);
            }
            break;


        // case 'users':
        //     $controller = new UserController($db);
        //     if ($action === 'GET') {
        //         echo $controller->getuserdetails();
        //     } 
        //     elseif ($action === 'POST') {
        //         echo $controller->createuser($data);
        //     } 
        //     elseif ($action === 'DELETE') {
        //         echo $controller->deleteuser($data);
        //     }
        //     elseif ($action === 'PUT') {
        //         echo $controller->updateuser($data);
        //     }
        //     elseif ($action === 'PATCH') {
        //         echo $controller->patchuser($data);
        //     }
        //     break;

        // case 'customer':
        //     $controller = new CustomerController($db);
        //     if ($action === 'GET') {
        //         echo $controller->getcustomer();
        //     } 
        //     elseif ($action === 'POST') {
        //         echo $controller->createcustomer($data);
        //     }
        //     elseif ($action === 'PUT') {
        //         echo $controller->updatecustomer($data);
        //     }
        //     elseif ($action === 'DELETE') {
        //         echo $controller->deletecustomer($data);
        //     }
        //     break;


    }

    
}    
else{
    echo "Token Failed";
} 



// Repeat for 'customers' and 'subcons'
?>