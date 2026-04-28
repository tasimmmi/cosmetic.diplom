<?php
/**
 * Тест отправки email
 */

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем автозагрузку Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Загружаем .env
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Exception $e) {
    echo "<p style='color:orange;'>⚠️ .env файл не найден</p>";
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест отправки Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 20px; }
        h2 { color: #666; font-size: 18px; margin-top: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #5a67d8; }
        .success { color: #28a745; padding: 15px; background: #d4edda; border-radius: 5px; margin: 20px 0; }
        .error { color: #dc3545; padding: 15px; background: #f8d7da; border-radius: 5px; margin: 20px 0; }
        .info { color: #17a2b8; padding: 15px; background: #d1ecf1; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Тест отправки Email</h1>
        
        <?php
        // Показываем настройки SMTP
        echo "<h2>📋 Настройки SMTP из .env:</h2>";
        echo "<table>";
        echo "<tr><th>Параметр</th><th>Значение</th></tr>";
        echo "<tr><td>SMTP_HOST</td><td>" . ($_ENV['SMTP_HOST'] ?? '<span style="color:red;">НЕ ЗАДАН</span>') . "</td></tr>";
        echo "<tr><td>SMTP_PORT</td><td>" . ($_ENV['SMTP_PORT'] ?? '<span style="color:red;">НЕ ЗАДАН</span>') . "</td></tr>";
        echo "<tr><td>SMTP_SECURE</td><td>" . ($_ENV['SMTP_SECURE'] ?? '<span style="color:red;">НЕ ЗАДАН</span>') . "</td></tr>";
        echo "<tr><td>SMTP_USER</td><td>" . ($_ENV['SMTP_USER'] ?? '<span style="color:red;">НЕ ЗАДАН</span>') . "</td></tr>";
        echo "<tr><td>SMTP_PASSWORD</td><td>" . (empty($_ENV['SMTP_PASSWORD']) ? '<span style="color:red;">НЕ ЗАДАН</span>' : '✅ Задан (скрыт)') . "</td></tr>";
        echo "<tr><td>EMAIL_FROM</td><td>" . ($_ENV['SMTP_FROM'] ?? '<span style="color:red;">НЕ ЗАДАН</span>') . "</td></tr>";
        echo "</table>";
        
        // Обработка отправки
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $to = $_POST['email'] ?? '';
            
            if (!empty($to)) {
                echo "<h2>📤 Отправка письма на: " . htmlspecialchars($to) . "</h2>";
                
                try {
                    $mail = new PHPMailer(true);
                    
                    // Настройки SMTP
                    $mail->isSMTP();
                    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.mail.ru';
                    $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USER'] ?? '';
                    $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
                    
                    $secure = $_ENV['SMTP_SECURE'] ?? 'ssl';
                    if ($secure === 'ssl') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    
                    $mail->CharSet = 'UTF-8';
                    $mail->setFrom($_ENV['EMAIL_FROM'] ?? $mail->Username, 'Cosmetic Test');
                    $mail->addAddress($to);
                    
                    $mail->isHTML(true);
                    $mail->Subject = '🧪 Тестовое письмо от Cosmetic';
                    $mail->Body = "
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset='utf-8'></head>
                        <body style='font-family: Arial;'>
                            <h1>🧪 Тестовое письмо</h1>
                            <p>Это тестовое письмо от платформы <strong>Cosmetic</strong>.</p>
                            <p>Если вы его получили — настройки SMTP работают правильно! ✅</p>
                            <hr>
                            <p style='color: #666; font-size: 12px;'>Отправлено: " . date('Y-m-d H:i:s') . "</p>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Тестовое письмо от Cosmetic. Отправлено: " . date('Y-m-d H:i:s');
                    
                    // Отладка SMTP
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = function($str, $level) {
                        echo "<pre style='background:#f0f0f0; padding:5px; margin:2px;'>" . htmlspecialchars($str) . "</pre>";
                    };
                    
                    echo "<h3>🔍 Отладка SMTP:</h3>";
                    echo "<div style='background:#1e1e1e; color:#d4d4d4; padding:15px; border-radius:5px; font-family:monospace; font-size:12px; max-height:400px; overflow-y:auto;'>";
                    
                    $mail->send();
                    
                    echo "</div>";
                    echo "<div class='success'>✅ Письмо успешно отправлено на " . htmlspecialchars($to) . "!</div>";
                    
                } catch (Exception $e) {
                    echo "</div>";
                    echo "<div class='error'>";
                    echo "<strong>❌ Ошибка отправки:</strong><br>";
                    echo $e->getMessage() . "<br><br>";
                    echo "<strong>SMTP Error:</strong><br>";
                    echo $mail->ErrorInfo;
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>❌ Укажите email!</div>";
            }
        }
        ?>
        
        <h2>📝 Отправить тестовое письмо</h2>
        <form method="POST">
            <div class="form-group">
                <label>Email получателя:</label>
                <input type="email" name="email" placeholder="your-email@mail.ru" required>
            </div>
            <button type="submit">🚀 Отправить тест</button>
        </form>
        
        <h2>💡 Рекомендации</h2>
        <div class="info">
            <p><strong>Для mail.ru:</strong></p>
            <ul>
                <li>SMTP_HOST = smtp.mail.ru</li>
                <li>SMTP_PORT = 465</li>
                <li>SMTP_SECURE = ssl</li>
                <li>SMTP_USER = ваш_email@mail.ru</li>
                <li>SMTP_PASSWORD = пароль приложения (не обычный пароль!)</li>
                <li>EMAIL_FROM = ваш_email@mail.ru (должен совпадать с SMTP_USER)</li>
            </ul>
            <p><strong>Как получить пароль приложения в mail.ru:</strong></p>
            <ol>
                <li>Зайдите в почту mail.ru</li>
                <li>Настройки → Пароли для приложений</li>
                <li>Создать новый пароль</li>
                <li>Скопировать и вставить в SMTP_PASSWORD</li>
            </ol>
        </div>
        
        <hr>
        <p><a href="test-email.php">🔄 Сбросить и начать заново</a></p>
    </div>
</body>
</html>