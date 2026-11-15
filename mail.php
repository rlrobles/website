<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if (isset($_POST["send"])) {

    $name = trim($_POST["name"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $message = trim($_POST["message"] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        die("Please fill in all fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Please enter a valid email address.");
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubiusloren@gmail.com';
        $mail->Password = 'dhujmcssijdwpzrk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Sender
        $mail->setFrom('rubiusloren@gmail.com', 'ACES SBM Contact Form');

        // Recipient
        $mail->addAddress('rubiusloren@gmail.com');

        // Reply to visitor
        $mail->addReplyTo($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'ACES SBM Contact Form Message';

        $mail->Body = '
            <h2>New Contact Form Message</h2>
            <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
            <p><strong>Message:</strong></p>
            <p>' . nl2br(htmlspecialchars($message)) . '</p>
        ';

        $mail->AltBody = 
            "New Contact Form Message\n\n" .
            "Name: " . $name . "\n" .
            "Email: " . $email . "\n\n" .
            "Message:\n" . $message;

        $mail->send();

        echo "
        <script>
            alert('Message was sent successfully!');
            window.location.href = 'contact.php';
        </script>
        ";

    } catch (Exception $e) {
        echo "
        <script>
            alert('Message could not be sent. Please try again.');
            window.location.href = 'contact.php';
        </script>
        ";
    }
}
?>