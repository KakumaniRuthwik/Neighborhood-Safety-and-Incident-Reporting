<?php
// Function to sanitize input data
function sanitizeInput($data) {
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// <?php
// require 'vendor/autoload.php'; // PHPMailer

// function sanitizeInput($data) {
//     return htmlspecialchars(strip_tags(trim($data)));
// }

// function sendOTPEmail($toEmail, $otpCode) {
//     $mail = new PHPMailer\PHPMailer\PHPMailer(true);

//     try {
//         $mail->isSMTP();
//         $mail->Host = 'smtp.gmail.com';
//         $mail->SMTPAuth = true;
//         $mail->Username = getenv('SMTP_USER');
//         $mail->Password = getenv('SMTP_PASS');
//         $mail->SMTPSecure = 'tls';
//         $mail->Port = 587;

//         $mail->setFrom(getenv('SMTP_USER'), 'SafeCommunities');
//         $mail->addAddress($toEmail);
//         $mail->isHTML(true);

//         $mail->Subject = 'Your OTP for SafeCommunities';
//         $mail->Body    = "<h2>Hi!</h2><p>Your verification code is: <strong>$otpCode</strong></p>";
//         $mail->send();
//         return true;
//     } catch (Exception $e) {
//         error_log("Mail Error: {$mail->ErrorInfo}");
//         return false;
//     }
// }
// 

