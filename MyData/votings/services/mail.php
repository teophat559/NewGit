<?php
require_once __DIR__ . '/../lib/env.php';
$mail_host = env('MAIL_HOST', 'smtp.gmail.com');
$mail_port = env('MAIL_PORT', '587');
$mail_user = env('MAIL_USERNAME', 'your_email@gmail.com');
$mail_pass = env('MAIL_PASSWORD', 'your_password');
$mail_encryption = env('MAIL_ENCRYPTION', 'tls');

$mail = new PHPMailer(true);
try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = $mail_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $mail_user;
    $mail->Password   = $mail_pass;
    $mail->SMTPSecure = $mail_encryption;
    $mail->Port       = $mail_port;

    //Recipients
    $mail->setFrom('from@example.com', 'Mailer');
    $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
    $mail->addAddress('ellen@example.com');               // Name is optional
    $mail->addReplyTo('info@example.com', 'Information');
    $mail->addCC('cc@example.com');
    $mail->addBCC('bcc@example.com');

    // Attachments
    $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}