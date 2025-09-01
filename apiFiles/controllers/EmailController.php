<?php

    require '/var/myhrFiles/vendor/autoload.php';


    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use PHPMailer\PHPMailer\SMTP;



    class EmailController{


        // private $conn;

        // public function __construct($db){
        //     $this->conn=$db;
        // }

        // public function __destruct(){
        //     if($this->conn){
        //         $this->conn=null;
        //     }
        // }


        public function sendEmail($name,$email,$otp,$type){

            //$name,$email,$otp,$type
            // $name=trim($_POST['name']);
            // $email=trim($_POST['mail']);
            // $otp=trim($_POST['otp']);
            // $type=trim($_POST['type']);
    
            // Validate email address
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Invalid MailID";
            }
    
            $mail = new PHPMailer(true);  // Create a new PHPMailer instance
          
    
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';  // Specify your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'myhr.support@einlogica.com';  // SMTP username
                $mail->Password = '@A9M2d9R+07t';  // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                // Recipients
                $mail->setFrom('myhr.support@einlogica.com', 'myHR Support');
                $mail->addAddress($email, $name);  // Add a recipient
                // Content
                $mail->isHTML(true);
    
                
    
                // HTML Body
                if($type==="new"){
                    $mail->addBCC('chandran.jinu@gmail.com', 'Jinu Chnadran');
                    $mail->Subject = 'Welcome to myHR! Your Account Verification OTP';
                    $mail->Body = "
                    <p>Dear $name,</p>
                    <p>Welcome to myHR! We are thrilled to have you onboard.</p>
                    <p><strong>Your generated PIN is: </strong><br>
                    <h2 style='color: blue;'>$otp</h2>
                    <br>
                    <p>Thank you for choosing myHR, powered by <strong>Einlogica Solutions Pvt Ltd</strong>. We are committed to providing you with a seamless experience.</p>
                    <p>Best regards,</p>
                    <p><strong>The myHR Support Team</strong><br>
                    <em>Powered by Einlogica Solutions Pvt Ltd</em></p>";
                }
                else if($type==="newuser"){
    
                    // App Store Link
                    $appStoreLink = "https://apps.apple.com/in/app/myhr-plus/id6624298170";
                    $playStoreLink = "https://play.google.com/store/apps/details?id=com.einlogica.einlogica_hr";
                
    
                    // $mail->addBCC('chandran.jinu@gmail.com', 'Jinu Chnadran');
                    $mail->Subject = 'Welcome Onboard! Activate Your myHR Account Now';
                    $mail->Body = "
                    <p>Dear $name,</p>
                    <p>Welcome to myHR! We are thrilled to have you onboard.</p>
                    <p>Download the <strong>myHR</strong> app from the links below:</p>
                    <ul>
                        <li><a href='$appStoreLink'>Apple App Store</a></li>
                        <li><a href='$playStoreLink'>Google Play Store</a></li>
                    </ul>
                    <p>To access your account, use your registered mobile number along with the provided OTP as your initial login credentials.</p>
                    <h2 style='color: blue;'>$otp</h2>
                    <br>
                    <p>Thank you for choosing myHR. We are committed to providing you with a seamless experience.</p>
                    <br>
                    <p>Best regards,</p>
                    <p><strong>The myHR Support Team</strong><br>
                    <em>Powered by Einlogica Solutions Pvt Ltd</em></p>";
                }
                else{
                    $mail->Subject = 'myHR Access Code Reset';
                    $mail->Body = "
                    <p>Dear {$name},</p>
                    <p>Please use the One-Time Password (OTP) below to login to myHR application:</p>
                    <h2 style='color: #2d89ef;'>{$otp}</h2>
                    <p><b>Note:</b>Please do not share it with anyone for security reasons.</p>
                    <p>If you did not request this login, please contact your administrator</p>
                    <p>Thank you for using myHR.</p>
                    <br>
                    <p>Best regards,<br>
                    <b>myHR Support Team</b><br>
                    <small>Powered by Einlogica Solutions Pvt Ltd</small></p>";
                }
                
    
                
    
                
                // Send the email
                $mail->send();
                // echo 'Message has been sent successfully';
                return "Success";
            } catch (Exception $e) {
                // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                return "Failed";
            }
        }



        public function requestQuote($employerName,$contactEmail,$usermobile,$emp,$empCount){

            //$name,$email,$otp,$type
            // $name=trim($_POST['name']);
            // $email=trim($_POST['mail']);
            // $otp=trim($_POST['otp']);
            // $type=trim($_POST['type']);
    
            // Validate email address
            
    
            $mail = new PHPMailer(true);  // Create a new PHPMailer instance
          
    
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';  // Specify your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'myhr.support@einlogica.com';  // SMTP username
                $mail->Password = '@A9M2d9R+07t';  // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                // Recipients
                $mail->setFrom('myhr.support@einlogica.com', 'myHR Support');
                $mail->addAddress('chandran.jinu@gmail.com', 'Jinu Chandran');  // Add a recipient
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'myHR Plan Upgrade request from '.$emp;
                // $mail->Body    = 'Here is your OTP for myHR: 12345';
                // HTML Body
                $mail->Body = "
                    <p>Dear Sales Team,</p>
                    <p>You have received a new quote request for upgrading a myHR plan. Below are the details of the request:</p>
                    <ul>
                        <li><strong>Employer Name:</strong> $employerName</li>
                        <li><strong>Email Address:</strong> $contactEmail</li>
                        <li><strong>Contact Number:</strong> $usermobile</li>
                        <li><strong>Employer Code:</strong> $emp</li>
                        <li><strong>Required Employee Count:</strong> $empCount</li>
                    </ul>
                    <p>Please reach out to the employer promptly to provide the necessary details and assistance regarding the upgrade.</p>
                    <p>If you require additional information about this request, feel free to contact support at <a href='mailto:support@myhr.com'>support@myhr.com</a>.</p>
                    <p>Thank you for your prompt attention to this request.</p>
                    <p>Best regards,<br><strong>myHR Notification System</strong></p>
                ";
                
    
                // Send the email
                $mail->send();
                // echo 'Message has been sent successfully';
                return "Success";
            } catch (Exception $e) {
                // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                return "Failed";
            }
        }




        public function sendExpiry($email,$name,$type){

        
            $mail = new PHPMailer(true);  // Create a new PHPMailer instance
          
    
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';  // Specify your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'myhr.support@einlogica.com';  // SMTP username
                $mail->Password = '@A9M2d9R+07t';  // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
    
                // Recipients
                $mail->setFrom('myhr.support@einlogica.com', 'myHR Support');

                // foreach ($emails as $email) {
                //     error_log($email);
                //     $mail->addBCC($email);                             // Add CC
                // }
                $mail->addAddress($email, $name);
                // Content
                $mail->isHTML(true);
                
                // $mail->Body    = 'Here is your OTP for myHR: 12345';
                // HTML Body

                if($type==='Alert'){
                    error_log("Sending Expiry Email Alert");
                    $mail->Subject = 'myHR Subscription Renewal Alert';
                    $mail->Body = "
                        <p>Dear $name,</p>
                        <br>
                        <p>We hope this message finds you well.</p>
                        <p>This mail is to remind you that your <strong>myHR subscription</strong> is approaching its expiration date.</p> 
                        <p>To ensure uninterrupted access to the application and its features, please make the payment through myHR app.</p>
                        <br>
                        <p>Best Regards,</p>
                        <p>myHR Support</p>
                    ";
                }
                else if($type==='Warning'){
                     error_log("Sending Expiry Email Warning");
                    $expiryDate = date('F 7, Y');
                    $mail->Subject = 'myHR Subscription Renewal Warning';
                    $mail->Body = "
                        <p>Dear $name,</p>
                        <br>
                        <p>We hope this message finds you well.</p>
                        <p>This mail is to inform you that your <strong>myHR subscription</strong> has passed its expiration date.</p> 
                        <p>To ensure uninterrupted access to the application and its features, please make the payment within <strong>{$expiryDate}</strong>.</p>
                        <br>
                        <p>Best Regards,</p>
                        <p><strong>myHR Support</strong></p>
                    ";
                }
                else if($type==='Lock'){
                     error_log("Sending Expiry Email Lock");
                    $expiryDate = date('F 7, Y');
                    $mail->Subject = 'myHR Subscription has been expired';
                    $mail->Body = "
                        <p>Dear $name,</p>
                        <br>
                        <p>We hope this message finds you well.</p>
                        <p>This mail is to inform you that your <strong>myHR subscription</strong> has beed locked.</p> 
                        <p>Please make the payment to gain full access.</p>
                        <br>
                        <p>Best Regards,</p>
                        <p><strong>myHR Support</strong></p>
                    ";
                }
                
                
    
                // Send the email
                $mail->send();
                // error_log('Message has been sent successfully');
                return "Success";
            } catch (Exception $e) {
                // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                return "Failed";
            }
        }




        



    }


?>