<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure()
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = isset($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com';
            $this->mailer->Port = isset($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = isset($_ENV['SMTP_USER']) ? $_ENV['SMTP_USER'] : '';
            $this->mailer->Password = isset($_ENV['SMTP_PASSWORD']) ? $_ENV['SMTP_PASSWORD'] : '';
            
            $secure = isset($_ENV['SMTP_SECURE']) ? $_ENV['SMTP_SECURE'] : 'tls';
            if ($secure === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $this->mailer->CharSet = 'UTF-8';
            $fromEmail = isset($_ENV['EMAIL_FROM']) ? $_ENV['EMAIL_FROM'] : 'noreply@cosmetic.helioho.st';
            $this->mailer->setFrom($fromEmail, 'Cosmetic Platform');
            
        } catch (Exception $e) {
            LoggerService::error('Email service configuration failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить письмо подтверждения email
     */
    public function sendVerificationEmail($email, $token, $name, $type = 'verification')
    {
        if ($type === 'password_reset') {
            return $this->sendPasswordResetEmail($email, $token, $name);
        }
        
        $verificationLink = (isset($_ENV['CLIENT_URL']) ? $_ENV['CLIENT_URL'] : 'http://localhost:3000') . "/verify-email.html?token=$token";
        
        $subject = 'Подтверждение Email - Cosmetic';
        
        $html = $this->getVerificationTemplate($name, $verificationLink);
        
        return $this->send($email, $subject, $html);
    }

    /**
     * Отправить письмо сброса пароля
     */
    public function sendPasswordResetEmail($email, $token, $name)
    {
        $resetLink = (isset($_ENV['CLIENT_URL']) ? $_ENV['CLIENT_URL'] : 'http://localhost:3000') . "/reset-password.php?token=$token";
        
        $subject = 'Сброс пароля - Cosmetic';
        
        $html = $this->getPasswordResetTemplate($name, $resetLink);
        
        return $this->send($email, $subject, $html);
    }

    /**
     * Отправить подтверждение записи
     */
    public function sendBookingConfirmation($email, $booking)
    {
        $subject = 'Запись подтверждена - Cosmetic';
        
        $html = $this->getBookingTemplate($booking);
        
        return $this->send($email, $subject, $html);
    }

    /**
     * Шаблон письма подтверждения email
     */
    private function getVerificationTemplate($name, $link)
    {
        $name = htmlspecialchars($name ?: 'Пользователь');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                         color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 24px; 
                         background: #667eea; color: white; text-decoration: none; 
                         border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Добро пожаловать в Cosmetic!</h1>
                </div>
                <div class='content'>
                    <p>Здравствуйте, {$name}!</p>
                    <p>Спасибо за регистрацию. Пожалуйста, подтвердите ваш email адрес.</p>
                    <p style='text-align: center;'>
                        <a href='$link' class='button'>Подтвердить Email</a>
                    </p>
                    <p>Или скопируйте эту ссылку:</p>
                    <p style='word-break: break-all;'>$link</p>
                    <p><strong>Ссылка действительна 24 часа.</strong></p>
                    <p>Если вы не регистрировались, просто проигнорируйте это письмо.</p>
                </div>
                <div class='footer'>
                    <p>© 2026 Cosmetic Platform. Все права защищены.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Шаблон письма сброса пароля
     */
    private function getPasswordResetTemplate($name, $link)
    {
        $name = htmlspecialchars($name ?: 'Пользователь');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 30px; 
                         text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 24px; 
                         background: #dc3545; color: white; text-decoration: none; 
                         border-radius: 5px; margin: 20px 0; }
                .warning { color: #dc3545; font-weight: bold; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Сброс пароля</h1>
                </div>
                <div class='content'>
                    <p>Здравствуйте, {$name}!</p>
                    <p>Мы получили запрос на сброс пароля для вашей учетной записи.</p>
                    <p style='text-align: center;'>
                        <a href='$link' class='button'>Сбросить пароль</a>
                    </p>
                    <p>Или скопируйте эту ссылку:</p>
                    <p style='word-break: break-all;'>$link</p>
                    <p class='warning'>⚠️ Ссылка действительна 1 час.</p>
                    <p>Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>
                </div>
                <div class='footer'>
                    <p>© 2026 Cosmetic Platform. Все права защищены.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Шаблон письма подтверждения записи
     */
    private function getBookingTemplate($booking)
    {
        $clientName = htmlspecialchars(isset($booking['client_name']) ? $booking['client_name'] : 'Клиент');
        $cosmetologistName = htmlspecialchars(isset($booking['cosmetologist_name']) ? $booking['cosmetologist_name'] : 'Косметолог');
        $serviceName = htmlspecialchars(isset($booking['service_name']) ? $booking['service_name'] : 'Услуга');
        $datetime = isset($booking['datetime']) ? $booking['datetime'] : '';
        $price = isset($booking['price']) ? $booking['price'] : '0';
        $address = htmlspecialchars(isset($booking['address']) ? $booking['address'] : '');
        $phone = htmlspecialchars(isset($booking['cosmetologist_phone']) ? $booking['cosmetologist_phone'] : '');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 30px; 
                         text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
                .detail-label { font-weight: bold; width: 150px; }
                .detail-value { flex: 1; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Запись подтверждена!</h1>
                </div>
                <div class='content'>
                    <p>Здравствуйте, {$clientName}!</p>
                    <p>Ваша запись успешно создана. Детали:</p>
                    <div class='details'>
                        <div class='detail-row'>
                            <div class='detail-label'>Косметолог:</div>
                            <div class='detail-value'>{$cosmetologistName}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Услуга:</div>
                            <div class='detail-value'>{$serviceName}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Дата и время:</div>
                            <div class='detail-value'>{$datetime}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Стоимость:</div>
                            <div class='detail-value'>{$price} BYN</div>
                        </div>"
                        . ($address ? "
                        <div class='detail-row'>
                            <div class='detail-label'>Адрес:</div>
                            <div class='detail-value'>{$address}</div>
                        </div>" : "") . "
                    </div>
                    " . ($phone ? "<p><strong>Телефон косметолога:</strong> {$phone}</p>" : "") . "
                    <p>Если у вас возникли вопросы, свяжитесь с косметологом.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Отправить email
     */
    private function send($to, $subject, $html)
{
    try {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($to);
        $this->mailer->Subject = $subject;
        $this->mailer->isHTML(true);
        $this->mailer->Body = $html;
        $this->mailer->AltBody = strip_tags($html);

        // Отладка
        error_log("=== SENDING EMAIL ===");
        error_log("To: " . $to);
        error_log("Subject: " . $subject);
        error_log("SMTP Host: " . $this->mailer->Host);
        error_log("SMTP User: " . $this->mailer->Username);

        $result = $this->mailer->send();
        
        LoggerService::info('Email sent successfully', ['to' => $to, 'subject' => $subject]);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("=== EMAIL ERROR ===");
        error_log("Error: " . $e->getMessage());
        error_log("SMTP Error: " . $this->mailer->ErrorInfo);
        
        LoggerService::error('Failed to send email', [
            'to' => $to,
            'error' => $e->getMessage(),
            'smtp_error' => $this->mailer->ErrorInfo
        ]);
        
        return false;
    }
}
}