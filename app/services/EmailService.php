<?php

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    public function enviarHtml(string $to, string $subject, string $html, array $options = []): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Correo destino invalido.');
        }

        $from = trim((string)($options['from'] ?? $this->env('SMTP_FROM', 'no-reply@localhost')));
        $fromName = trim((string)($options['from_name'] ?? $this->env('SMTP_FROM_NAME', 'Sistema de Creditos')));

        $host = trim((string)$this->env('SMTP_HOST', ''));
        $user = trim((string)$this->env('SMTP_USER', ''));
        $pass = trim((string)$this->env('SMTP_PASS', ''));
        $port = (int)$this->env('SMTP_PORT', '587');
        $encryption = strtolower(trim((string)$this->env('SMTP_ENCRYPTION', 'tls')));
        $smtpAuthRaw = strtolower(trim((string)$this->env('SMTP_AUTH', 'true')));
        $smtpAuth = !in_array($smtpAuthRaw, ['0', 'false', 'no', 'off'], true);
        $timeout = (int)$this->env('SMTP_TIMEOUT', '20');
        $debugLevel = (int)$this->env('SMTP_DEBUG', '0');

        if ($host === '' || $user === '' || $pass === '') {
            throw new Exception('SMTP no configurado correctamente. Define SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS y SMTP_ENCRYPTION en el entorno de Apache.');
        }

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $from = $user;
        }

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('SMTP_FROM no es un correo válido.');
        }

        $secure = '';
        if ($encryption === 'ssl' || $encryption === 'smtps') {
            $secure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $secure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port > 0 ? $port : 587;
            $mail->SMTPAuth = $smtpAuth;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = $secure;
            $mail->Timeout = $timeout > 0 ? $timeout : 20;
            $mail->SMTPDebug = max(0, $debugLevel);
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($from, $fromName !== '' ? $fromName : 'Sistema de Creditos');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html)));

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new Exception('No se pudo enviar el correo: ' . $e->getMessage());
        } catch (Throwable $e) {
            throw new Exception('Error inesperado al enviar correo: ' . $e->getMessage());
        }
    }

    private function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return (string)$value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string)$_SERVER[$key];
        }

        return $default;
    }
}
