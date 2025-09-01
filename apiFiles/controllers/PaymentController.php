<?php

    class PaymentController{


        private $conn;

        public function __construct($db){
            $this->conn=$db;
        }

        public function __destruct(){
            if($this->conn){
                $this->conn=null;
            }
        }

        public function getSubscription($data){

            if(!isset($data['usermobile']) || !isset($data['emp'])){
                return;
            }
            
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            
            // $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            // $stm = $this->conn->prepare($qry);
            // $stm->execute();
            // $tz = $stm->fetch(PDO::FETCH_ASSOC);
            // $timezone = $tz['TimeZone'];
        
            // date_default_timezone_set($timezone);
            // // date_default_timezone_set('Asia/Kolkata');
            // $Time=date('H:i:s');
            // $CurrDate=date('Y-m-d');
            
            //Check user
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile' and `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
    
            $rowCount = $stm->rowCount();

            $query = "SELECT * FROM subscriptions WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetchall(PDO::FETCH_ASSOC);
            
            if($rowCount!=0){
                
                return json_encode($row);
            }
            else{
                foreach ($row as &$item) {
                    foreach ($item as $key => &$value) {
                        $value = '0';
                    }
                }
                unset($item); // break reference
                unset($value); // break reference
                return json_encode($row);
            }
    
    
        }
    
        public function updatePayment($data){
    
            if(!isset($data['usermobile']) || !isset($data['emp']) || !isset($data['id'])){
                return;
            }
    
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            // $amount=trim($data['amount']);
            // $qty=trim($data['qty']);
            $id=trim($data['id']);
            $order=trim($data['order']);
            $status=trim($data['status']);
    
            // date_default_timezone_set('Asia/Kolkata');
            // $Time=date('H:i:s');
            // $CurrDate=date('Y-m-d');
    
            $query = "SELECT * FROM `payments` WHERE `OrderID`='$order'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            $toDate= $row['ToDate'];
            $currentStatus = $row['Status'];
    
            // $rate = $row['Amount'];
            // $fromDate= $row['Expiry'];
            // $toDate= new DateTime($fromDate);
            // $toDate->modify('+'.$qty.' months');
            // $modifiedDate = $toDate->format('Y-m-d');
    
    
            if($currentStatus === 'In Progress'){
                $query = "UPDATE `payments` SET `TransactionID`='$id',`Status`='$status' WHERE `OrderID`='$order'";
                $stm = $this->conn->prepare($query);
                if($stm->execute()){
    
                    if($status != 'Failed'){
                        $query = "UPDATE subscriptions SET `Expiry`='$toDate' WHERE `Employer`='$emp'";
                        $stm = $this->conn->prepare($query);
                        $stm->execute();
                    }
                    return "Success";
                }
                else{
                    return "Failed";
                }
            }
            // $query = "INSERT INTO `payments` (`Employer`,`Amount`,`Unit`,`Total`,`FromDate`,`ToDate`,`TransactionID`,`OrderID`,`Date`,`Time`) VALUES ('$emp','$rate','$qty','$amount','$fromDate','$modifiedDate','$id','$order','$CurrDate','$Time')";
            
        }
    
    
        private function insertTransaction($usermobile,$emp,$amount,$qty,$order){
    
            // if(!isset($data['usermobile']) || !isset($data['emp']) || !isset($data['order'])){
            //     return;
            // }
    
            // $usermobile=trim($data['usermobile']);
            // $emp=trim($data['emp']);
            // $amount=trim($data['amount']);
            // $qty=trim($data['qty']);
            // // $id=trim($data['id']);
            // $order=trim($data['order']);

            $qry = "SELECT `TimeZone` FROM `settings` WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($qry);
            $stm->execute();
            $tz = $stm->fetch(PDO::FETCH_ASSOC);
            $timezone = $tz['TimeZone'];
        
            date_default_timezone_set($timezone);
    
            // date_default_timezone_set('Asia/Kolkata');
            $Time=date('H:i:s');
            $CurrDate=date('Y-m-d');
    
            $query = "SELECT * FROM subscriptions WHERE `Employer`='$emp'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $row = $stm->fetch(PDO::FETCH_ASSOC);
    
    
            $rate = $row['Amount'];
            $fromDate= $row['Expiry'];
            $toDate= new DateTime($fromDate);
            $toDate->modify('+'.$qty.' months');
            $modifiedDate = $toDate->format('Y-m-d');
    
    
            
            $query = "INSERT INTO `payments` (`Employer`,`Amount`,`Unit`,`Total`,`FromDate`,`ToDate`,`TransactionID`,`OrderID`,`Date`,`Time`,`Status`) VALUES ('$emp','$rate','$qty','$amount','$fromDate','$modifiedDate','NA','$order','$CurrDate','$Time','In Progress')";
            $stm = $this->conn->prepare($query);
            if($stm->execute()){
    
                // $query = "UPDATE subscriptions SET `Expiry`='$modifiedDate' WHERE `Employer`='$emp'";
                // $stm = $this->conn->prepare($query);
                // $stm->execute();
    
                return "Success";
            }
            else{
                return "Failed";
            }
        }
    
        public function getOrderid($data){
    
            $amount=trim($data['amount']);
            $usermobile=trim($data['usermobile']);
            $qty=trim($data['qty']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile'  and `Employer`='$emp' and `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount>0){
                $apiKey = 'rzp_live_aUnuQeSvJAEjzs'; // Replace with your Razorpay API Key
                $apiSecret = 'ZPCDYKMuoem1i8uC7TKJYdkP'; // Replace with your Razorpay API Secret
                // $apiKey = 'rzp_test_9OpZHzb53yxKPB'; // Replace with your Razorpay API Key
                // $apiSecret = 'OEqQbCxvuAPmZ7lboBlzZ053'; // Replace with your Razorpay API Secret
    
                // Razorpay API endpoint for creating orders
                $url = 'https://api.razorpay.com/v1/orders';
    
                // Order data
                $orderData = [
                    // 'receipt'         => 'order_rcptid_11',
                    'amount'          => $amount*100, // Amount in paise (50000 paise = 500 INR)
                    'currency'        => 'INR',
                    'payment_capture' => 1 // 1 for automatic capture, 0 for manual
                ];
    
                // Initialize cURL
                $ch = curl_init($url);
    
                // Set cURL options
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_USERPWD, "$apiKey:$apiSecret");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    
                // Execute cURL request
                $response = curl_exec($ch);
    
                // Check for errors
                if (curl_errno($ch)) {
                    echo 'cURL error: ' . curl_error($ch);
                    curl_close($ch);
                    exit;
                }
    
                // Close cURL resource
                curl_close($ch);
    
                // Decode the JSON response
                $responseData = json_decode($response, true);
    
                // Check if response contains order ID
                if (isset($responseData['id'])) {
                    $orderId = $responseData['id'];
                    $this->insertTransaction($usermobile,$emp,$amount,$qty,$orderId);
                    echo "$orderId";
                } else {
                    echo "Failed";
                }
            }
            else{
                echo "Failed";
            }
        }
    
        public function validateSignature($data){
    
            $razorpaySignature = $data['razorpay_signature']; // Signature sent by Razorpay
            $razorpayPaymentId = $data['razorpay_payment_id']; // Payment ID sent by Razorpay
            $razorpayOrderId = $data['razorpay_order_id']; // Order ID sent by Razorpay
    
            // Your Razorpay API Secret
            $apiSecret = 'ZPCDYKMuoem1i8uC7TKJYdkP'; // Replace with your actual Razorpay API Secret
    
            // Generate the expected signature using the API Secret and the provided parameters
            $generatedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $apiSecret);
    
            // Validate the signature
            if (hash_equals($razorpaySignature, $generatedSignature)) {
                echo "Signature is valid";
                // Proceed with processing the payment
            } else {
                echo "Signature is invalid";
                // Handle the invalid signature scenario
            }
        }
    
        public function getPaymentList($data){
    
            
            $usermobile=trim($data['usermobile']);
            $emp=trim($data['emp']);
            
            $query = "SELECT * FROM `UserInfo` WHERE `Mobile`='$usermobile'  and `Employer`='$emp' and `Permission`='Admin'";
            $stm = $this->conn->prepare($query);
            $stm->execute();
            $rowCount = $stm->rowCount();
            
            if($rowCount>0){
                
                $query = "SELECT * FROM payments WHERE `Employer`='$emp' ORDER BY ID DESC";
                $stm = $this->conn->prepare($query);
                $stm->execute();
                $row = $stm->fetchall(PDO::FETCH_ASSOC);
                return json_encode($row);
            }
            else{
                return "Failed";
            }
    
        }
    
    
    
        // public function payment($data){
    
           
        //     // webhook.php
        //     $secret = "70bb4fa90a3b9a375946ef1b8fec"; // Set in Razorpay dashboard
        //     // $secret = "test_secret"; // Set in Razorpay dashboard
        //     $input = file_get_contents('php://input');
        //     $headers = getallheaders();
    
        //     // error_log($headers);
        //     // Validate Razorpay signature
    
        //     if (!isset($headers['X-Razorpay-Signature'])) {
        //         http_response_code(400);
        //         exit("Invalid request");
        //     }
    
        //     $sig = $headers['X-Razorpay-Signature'];
        //     $expectedSig = hash_hmac('sha256', $input, $secret);
    
        //     if (!hash_equals($expectedSig, $sig)) {
        //         http_response_code(400);
        //         exit("Signature mismatch");
        //     }
    
        //     // Decode JSON data
        //     $data = json_decode($input, true);
        //     $paymentId = $data['payload']['payment']['entity']['id'];
        //     $orderId = $data['payload']['payment']['entity']['order_id'];
        //     $status = $data['payload']['payment']['entity']['status'];
        //     //(`Employer`,`Amount`,`Unit`,`Total`,`FromDate`,`ToDate`,`TransactionID`,`OrderID`,`Date`,`Time`) VALUES ('$emp','$rate','$qty','$amount','$fromDate','$modifiedDate','Pending','$order','$CurrDate','$Time')
            
        //     $query = "SELECT * FROM `payments` WHERE `OrderID`='$orderId'";
        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
        //     $row = $stm->fetch(PDO::FETCH_ASSOC);
        //     $toDate= $row['ToDate'];
        //     $emp = $row['Employer'];
            
            
        //     $query = "UPDATE `payments` SET `TransactionID`='$paymentId',`Status`='$status' WHERE `OrderID`='$orderId';
        //               UPDATE subscriptions SET `Expiry`='$toDate' WHERE `Employer`='$emp'";
        //     $stm = $this->conn->prepare($query);
        //     $stm->execute();
 
        //     http_response_code(200);
        //     echo "Webhook processed successfully";
    
    
    
        // }

        



    }


?>