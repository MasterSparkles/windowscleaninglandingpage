<?php

namespace Application\controllers\appcontrol;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailControl
{
    private PHPMailer $mailer;
    private string $fromEmail;

    public function __construct(?string $fromEmail = null)
    {
        $this->fromEmail = $fromEmail ?: (getenv('MAIL_FROM') ?: 'mastersparklescleaning@gmail.com');
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }

    private function setupSMTP(): void
    {
        $username = getenv('MAIL_USERNAME') ?: 'mastersparklescleaning@gmail.com';
        $password = getenv('MAIL_PASSWORD') ?: 'bucqvoeimmopvha';
        $debug = (getenv('MAIL_DEBUG') === '1');

        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $username;
        $this->mailer->Password = $password;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = 465;
        $this->mailer->Timeout = 30;
        $this->mailer->SMTPKeepAlive = false;

        $this->mailer->CharSet = 'UTF-8';
        if ($debug) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = static function (string $str, int $level): void {
                error_log('PHPMailer[' . $level . ']: ' . $str);
            };
        }

        $this->mailer->setFrom($this->fromEmail, 'Master Sparkle\'s Cleaning Service');
    }

    public function sendTo($TO_EMAIL, $SUBJECT, $BODY, $isHTML = false): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearReplyTos();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            $this->mailer->addAddress((string)$TO_EMAIL);
            $this->mailer->Subject = (string)$SUBJECT;
            $this->mailer->Body = (string)$BODY;
            $this->mailer->isHTML($isHTML);

            if ($isHTML) {
                $this->mailer->AltBody = trim(html_entity_decode(strip_tags((string)$BODY)));
            }

            $this->mailer->addReplyTo($this->fromEmail);

            $result = $this->mailer->send();
            error_log('PHPMailer send result: ' . ($result ? 'true' : 'false') . ' to: ' . (string)$TO_EMAIL);
            if (!$result) {
                error_log('PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            }
            return $result;
        } catch (Exception $e) {
            error_log('EmailControl PHPMailer exception: ' . $e->getMessage());
            return false;
        }
    }
}