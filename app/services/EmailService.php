<?php

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

        if ($host === '' || $user === '' || $pass === '') {
            throw new Exception('SMTP no configurado correctamente. Define SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS y SMTP_ENCRYPTION en el entorno de Apache.');
        }

        $this->enviarPorSmtp($host, $port, $encryption, $user, $pass, $from, $fromName, $to, $subject, $html);
    }

    private function enviarPorSmtp(
        string $host,
        int $port,
        string $encryption,
        string $user,
        string $pass,
        string $from,
        string $fromName,
        string $to,
        string $subject,
        string $html
    ): void {
        $transport = ($encryption === 'ssl') ? 'ssl://' : 'tcp://';
        $remote = $transport . $host . ':' . $port;

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            throw new Exception('No se pudo conectar al servidor SMTP: ' . $errstr);
        }

        stream_set_timeout($socket, 20);

        try {
            $this->assertResponse($socket, [220]);
            $this->sendCommand($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS', [220]);
                $cryptoEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new Exception('No se pudo establecer canal TLS con SMTP.');
                }
                $this->sendCommand($socket, 'EHLO localhost', [250]);
            }

            $this->sendCommand($socket, 'AUTH LOGIN', [334]);
            $this->sendCommand($socket, base64_encode($user), [334]);
            $this->sendCommand($socket, base64_encode($pass), [235]);

            $this->sendCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->sendCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->sendCommand($socket, 'DATA', [354]);

            $headers = [
                'Date: ' . date('r'),
                'From: ' . $this->formatearFrom($from, $fromName),
                'To: <' . $to . '>',
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = implode("\r\n", $headers) . "\r\n\r\n" . $html;
            $body = preg_replace('/(?m)^\./', '..', $body);

            fwrite($socket, $body . "\r\n.\r\n");
            $this->assertResponse($socket, [250]);

            $this->sendCommand($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function sendCommand($socket, string $command, array $expectedCodes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->assertResponse($socket, $expectedCodes);
    }

    private function assertResponse($socket, array $expectedCodes): void
    {
        [$code, $message] = $this->readResponse($socket);
        if (!in_array($code, $expectedCodes, true)) {
            throw new Exception('SMTP respondio ' . $code . ': ' . trim($message));
        }
    }

    private function readResponse($socket): array
    {
        $message = '';
        $code = 0;

        while (($line = fgets($socket, 515)) !== false) {
            $message .= $line;
            if (preg_match('/^(\d{3})([\s-])/', $line, $matches)) {
                $code = (int)$matches[1];
                if ($matches[2] === ' ') {
                    break;
                }
            }
        }

        if ($code === 0) {
            throw new Exception('No se recibio respuesta valida del servidor SMTP.');
        }

        return [$code, $message];
    }

    private function formatearFrom(string $email, string $name): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }

        return '"' . addslashes($name) . '" <' . $email . '>';
    }

    private function encodeHeader(string $text): string
    {
        if ($text === '') {
            return '';
        }

        return '=?UTF-8?B?' . base64_encode($text) . '?=';
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
