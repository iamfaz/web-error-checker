<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

function sendEmail($to, $subject, $message)
{
    
    global $mail; 

    try {
        
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
        $mail->isSMTP();
        $mail->Host       = 'email-smtp.us-east-1.amazonaws.com';
        $mail->SMTPAuth   = true; 
        $mail->Username   = 'AKIA6I6FJTMQ4CEO4K5Z'; 
        $mail->Password   = 'BKvHm22fPnAMosVW+3YUpGNtqasnfy45jiwhjn4Z1srw'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 

        $mail->setFrom('no-reply@osky.dev');
        $mail->addAddress($to);

        $mail->isHTML(true); 
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {

        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function writeLog($message)
{
    $logFilePath = 'error_log.txt';

    $logFile = fopen($logFilePath, 'a');

    fwrite($logFile, $message . PHP_EOL);

    fclose($logFile);
}

$jsonFilePath = 'urls.json';

if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);

    if ($jsonContent === false) {
        die("Error reading JSON file at $jsonFilePath\n");
    }

    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error decoding JSON: " . json_last_error_msg() . "\n");
    }

    if (isset($data['urls']) && is_array($data['urls'])) {
        foreach ($data['urls'] as $url) {
            $url = trim($url);

            if (empty($url)) {
                continue;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);

            if ($content === false) {
                writeLog("Failed to fetch content from $url: " . curl_error($ch));
                continue;
            }

            $errorMessages = ["Fatal error", "PHP Warning", "Notice", "Undefined index"];

            foreach ($errorMessages as $errorMessage) {
                if (strpos($content, $errorMessage) !== false) {
                    $subject = "Error found on $url";
                    $message = "Error message: $errorMessage";
                    writeLog($message);
                    sendEmail("fazila.azhari@osky.com.au", $subject, $message);
                }
            }

            curl_close($ch);
        }
    } else {
        echo "Invalid JSON format. Missing 'urls' key or 'urls' is not an array.\n";
    }
} else {
    echo "JSON file not found at $jsonFilePath\n";
}


?>
