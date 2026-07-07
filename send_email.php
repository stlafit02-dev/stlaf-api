<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username = 'stlaf.itdept@gmail.com';

    $mail->Password = 'ptvd upua zsep nrhi';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->setFrom('stlaf.itdept@gmail.com', 'STLAF Leave System');

    $mail->addAddress('stlaf.it02@gmail.com');

    $mail->isHTML(true);

    $mail->Subject = 'PHPMailer Test';

    $mail->Body = '<h2>Email is working!</h2><p>Your PHP backend can now send emails.</p>';

    $mail->send();

    echo "Email sent successfully.";

} catch (Exception $e) {

    echo "Email failed: {$mail->ErrorInfo}";
}