<?php
// Simple mailer wrapper. Configure SMTP credentials below for production.
// Try to use PHPMailer if available, otherwise fall back to mail().
function sendEmail($to, $subject, $body, $from = 'no-reply@webitech.fr', $fromName = 'Webitech'){
    // prefer PHPMailer if installed
    if(class_exists('PHPMailer\PHPMailer\PHPMailer')){
        try{
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            // TODO: replace with real SMTP credentials or environment vars
            $mail->Host = 'smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = 'username';
            $mail->Password = 'password';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom($from,$fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return true;
        }catch(Exception $e){
            error_log('PHPMailer error: ' . $e->getMessage());
            // fallback to mail()
        }
    }

    // fallback simple mail
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: ' . $fromName . ' <' . $from . '>' . "\r\n";
    return mail($to, $subject, $body, $headers);
}

?>
