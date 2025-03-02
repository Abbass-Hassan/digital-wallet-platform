<?php
// MailService.php in wallet-server/utils

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

class MailService
{
    private $mailer;

    public function __construct()
    {
        // Load mail configuration
        // Adjust path if mail_config.php is in a different folder
        $config = require __DIR__ . '/../connection/mail_config.php';

        $this->mailer = new PHPMailer(true);

        $this->mailer->SMTPDebug = 2;
        try {
            //Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $config['host'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $config['username'];
            $this->mailer->Password   = $config['password'];
            $this->mailer->SMTPSecure = 'tls'; // or 'ssl' if using port 465
            $this->mailer->Port       = $config['port'];

            // Sender info
            $this->mailer->setFrom($config['from_email'], $config['from_name']);
        } catch (Exception $e) {
            // Handle mailer initialization error if needed
        }
    }

    public function sendMail($to, $subject, $body)
    {
        try {
            // Clear any previous addresses (in case this mailer is reused)
            $this->mailer->clearAddresses();

            // Add the new recipient
            $this->mailer->addAddress($to);

            // Subject & Body
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true); // Use HTML emails
            $this->mailer->Body    = $body;

            // Send the email
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            // Log or handle the error if needed
            return false;
        }
    }
}
