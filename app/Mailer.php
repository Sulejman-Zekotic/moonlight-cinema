<?php

declare(strict_types=1);

final class Mailer
{
    public function __construct(private array $config)
    {
    }

    public function sendReservationTicket(array $ticket): array
    {
        return $this->deliver(
            (string) $ticket['to'],
            'Moonlight Cinema - Vaša karta',
            $this->ticketHtml($ticket),
            [
                'kind' => 'reservation_ticket',
                'payload' => [
                    'movie' => $ticket['movie'],
                    'date' => $ticket['date'],
                    'time' => $ticket['time'],
                    'hall' => $ticket['hall'],
                    'seats' => $ticket['seats'] ?? [],
                    'cancel_url' => $ticket['cancel_url'],
                    'pay_url' => $ticket['pay_url'] ?? '',
                    'payment_status' => $ticket['payment_status'] ?? 'Nije plaćena',
                    'starts_at' => $ticket['starts_at'] ?? '',
                ],
            ]
        );
    }

    public function sendPasswordResetLink(array $payload): array
    {
        return $this->deliver(
            (string) $payload['to'],
            'Moonlight Cinema - Reset lozinke',
            $this->passwordResetHtml($payload),
            [
                'kind' => 'password_reset',
                'payload' => [
                    'name' => $payload['name'] ?? '',
                    'reset_url' => $payload['reset_url'] ?? '',
                    'expires_at' => $payload['expires_at'] ?? '',
                ],
            ]
        );
    }

    private function deliver(string $to, string $subject, string $html, array $context): array
    {
        $result = [
            'sent' => false,
            'driver' => 'log',
            'error' => null,
        ];

        try {
            $transport = $this->configuredTransport();
            $result = match ($transport) {
                'smtp' => $this->sendViaSmtp($to, $subject, $html),
                'mail' => $this->sendViaMail($to, $subject, $html),
                default => $this->sendAutomatically($to, $subject, $html),
            };
        } catch (Throwable $throwable) {
            error_log('MAIL ERROR DELIVER: ' . $throwable->getMessage());
            $result['error'] = $throwable->getMessage();
        }

        if (!$result['sent']) {
            error_log('MAIL ERROR RESULT: driver=' . ($result['driver'] ?? 'unknown') . ' error=' . ($result['error'] ?? 'unknown'));
        }

        if (!$result['sent'] && $this->shouldUseLogFallback($result)) {
            $result = [
                'sent' => true,
                'driver' => 'log',
                'error' => null,
            ];
        }

        $this->logAttempt($to, $subject, $context, $result);

        return $result;
    }
        private function passwordResetHtml(array $payload): string
    {
        return '
<!DOCTYPE html>
<html lang="bs">
<head>
  <meta charset="UTF-8">
  <title>Reset lozinke</title>
</head>
<body style="margin:0;padding:24px;background:#071628;font-family:Segoe UI,Arial,sans-serif;color:#f8f9fa;">
  <div style="max-width:640px;margin:0 auto;background:#13263b;border-radius:20px;padding:32px;border:1px solid rgba(255,255,255,0.08);">

    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-size:26px;font-weight:800;letter-spacing:0.04em;">
        MOONLIGHT CINEMA
      </div>

      <div style="margin-top:8px;color:#c4cfd4;font-size:14px;">
        Zahtjev za reset lozinke
      </div>
    </div>

    <p style="margin:0 0 16px;color:#d7e4ef;line-height:1.7;">
      Zdravo ' . e((string) ($payload['name'] ?? '')) . ',
      kliknite na dugme ispod kako biste postavili novu lozinku za svoj nalog.
    </p>

    <div style="text-align:center;margin:22px 0;">
      <a href="' . e((string) ($payload['reset_url'] ?? '')) . '"
         style="display:inline-block;width:232px;height:44px;line-height:44px;border-radius:16px;background:#4ea8de;color:#071628;text-decoration:none;font-weight:700;text-align:center;font-size:15px;">
         Postavi novu lozinku
      </a>
    </div>

    <p style="margin:0 0 12px;color:#c4cfd4;line-height:1.6;">
      Link vrijedi do
      <strong style="color:#ffffff;">' . e(format_date_local((string) ($payload['expires_at'] ?? ''), 'd.m.Y H:i')) . '</strong>.
      Ako niste tražili reset lozinke, slobodno zanemarite ovu poruku.
    </p>

    <p style="margin:0;color:#9fb3c8;font-size:13px;text-align:center;">
      Moonlight Cinema, Mostar
    </p>

  </div>
</body>
</html>';
    }
    private function sendAutomatically(string $to, string $subject, string $html): array
    {
        $smtpHost = trim((string) ($this->config['smtp']['host'] ?? ''));

        if ($smtpHost !== '') {
            $result = $this->sendViaSmtp($to, $subject, $html);
            if ($result['sent']) {
                return $result;
            }
        }

        return $this->sendViaMail($to, $subject, $html);
    }

    private function sendViaMail(string $to, string $subject, string $html): array
    {
        $encodedSubject = $this->encodeHeader($subject);
        $fromName = $this->config['from_name'] ?? 'Moonlight Cinema';
        $fromEmail = $this->config['from'] ?? 'info@moonlightcinema.ba';
        $replyTo = $this->config['reply_to'] ?? $fromEmail;

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->formatAddress((string) $fromEmail, (string) $fromName),
            'Reply-To: ' . $replyTo,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $sent = @mail($to, $encodedSubject, $html, implode("\r\n", $headers));

        return [
            'sent' => $sent,
            'driver' => 'mail',
            'error' => $sent ? null : 'PHP mail() nije uspio poslati poruku.',
        ];
    }

    private function sendViaSmtp(string $to, string $subject, string $html): array
    {
        $smtp = $this->config['smtp'] ?? [];
        $host = trim((string) ($smtp['host'] ?? ''));
        $port = (int) ($smtp['port'] ?? 587);
        $username = (string) ($smtp['username'] ?? '');
        $password = (string) ($smtp['password'] ?? '');
        $encryption = strtolower((string) ($smtp['encryption'] ?? 'tls'));
        $timeout = (int) ($smtp['timeout'] ?? 15);

        if ($host === '') {
            return [
                'sent' => false,
                'driver' => 'smtp',
                'error' => 'SMTP host nije podešen.',
            ];
        }

        $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            $remoteHost . ':' . $port,
            $errorCode,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            return [
                'sent' => false,
                'driver' => 'smtp',
                'error' => trim((string) $errorMessage) !== '' ? trim((string) $errorMessage) : 'Neuspješna SMTP konekcija.',
            ];
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectSmtpCode($socket, [220]);
            $this->smtpCommand($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Neuspješan STARTTLS handshake.');
                }

                $this->smtpCommand($socket, 'EHLO localhost', [250]);
            }

            if ($username !== '') {
                $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
                $this->smtpCommand($socket, base64_encode($username), [334]);
                $this->smtpCommand($socket, base64_encode($password), [235]);
            }

            $fromEmail = (string) ($this->config['from'] ?? 'info@moonlightcinema.ba');
            $fromName = (string) ($this->config['from_name'] ?? 'Moonlight Cinema');
            $replyTo = (string) ($this->config['reply_to'] ?? $fromEmail);

            $this->smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->formatAddress($fromEmail, $fromName),
                'Reply-To: ' . $replyTo,
                'To: ' . $to,
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
            fwrite($socket, $body . "\r\n");

            $this->expectSmtpCode($socket, [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);

            return [
                'sent' => true,
                'driver' => 'smtp',
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            error_log('MAIL ERROR SMTP: ' . $throwable->getMessage());

            return [
                'sent' => false,
                'driver' => 'smtp',
                'error' => $throwable->getMessage(),
            ];
        } finally {
            fclose($socket);
        }
    }

    private function smtpCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->expectSmtpCode($socket, $expectedCodes);
    }

    private function expectSmtpCode($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (preg_match('/^\d{3}\s/', $line) === 1) {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException(trim($response) !== '' ? trim($response) : 'Neočekivan SMTP odgovor.');
        }

        return $response;
    }

    private function configuredTransport(): string
    {
        $transport = strtolower(trim((string) ($this->config['transport'] ?? 'auto')));

        return in_array($transport, ['auto', 'smtp', 'mail'], true) ? $transport : 'auto';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function formatAddress(string $email, string $name): string
    {
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function shouldUseLogFallback(array $result): bool
    {
        if (($result['driver'] ?? '') !== 'mail') {
            return false;
        }

        $smtpHost = trim((string) ($this->config['smtp']['host'] ?? ''));

        if ($smtpHost !== '') {
            return false;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host);

        return in_array($host, ['localhost', '127.0.0.1'], true);
    }

    private function logAttempt(string $to, string $subject, array $context, array $result): void
    {
        $logPath = (string) ($this->config['log_path'] ?? '');
        $logDir = dirname($logPath);

        if ($logPath !== '' && !is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = [
            'sent_at' => date('Y-m-d H:i:s'),
            'sent' => (bool) ($result['sent'] ?? false),
            'driver' => $result['driver'] ?? 'log',
            'error' => $result['error'] ?? null,
            'to' => $to,
            'subject' => $subject,
            'kind' => $context['kind'] ?? 'generic',
            'payload' => $context['payload'] ?? [],
        ];

        if ($logPath !== '') {
            file_put_contents(
                $logPath,
                json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    private function ticketHtml(array $ticket): string
    {
        $seatList = implode(', ', $ticket['seats'] ?? []);
        $paymentStatus = (string) ($ticket['payment_status'] ?? 'Nije plaćena');
        $normalizedPaymentStatus = strtolower(str_replace(['ć', 'č'], ['c', 'c'], $paymentStatus));
        $isPaid = in_array($normalizedPaymentStatus, ['placena', 'paid'], true);

        $payDeadlineHtml = '';
        if (!$isPaid && !empty($ticket['starts_at'])) {
            try {
                $deadline = new DateTime((string) $ticket['starts_at']);
                $deadline->modify('-30 minutes');

                $payDeadlineHtml = '
      <div style="margin-bottom:10px;color:#d7e4ef;">
        Najkasnije platiti do: <strong style="color:#ffffff;">' . e($deadline->format('d.m.Y, H:i')) . '</strong>
      </div>';
            } catch (Throwable) {
                $payDeadlineHtml = '';
            }
        }

        $qrHtml = '';
        if (!empty($ticket['qr_code_data_uri'])) {
            $qrHtml = '
    <div style="text-align:center;margin:22px 0;">
      <div style="color:#c4cfd4;font-size:14px;margin-bottom:10px;">QR kod karte</div>
      <img src="' . e((string) $ticket['qr_code_data_uri']) . '" alt="QR kod karte" style="width:150px;height:150px;border-radius:12px;background:#ffffff;padding:10px;">
    </div>';
        } elseif (!empty($ticket['qr_code_url'])) {
            $qrHtml = '
    <div style="text-align:center;margin:22px 0;">
      <div style="color:#c4cfd4;font-size:14px;margin-bottom:10px;">QR kod karte</div>
      <img src="' . e((string) $ticket['qr_code_url']) . '" alt="QR kod karte" style="width:150px;height:150px;border-radius:12px;background:#ffffff;padding:10px;">
    </div>';
        }

        $payButtonHtml = '';
        if (!$isPaid && !empty($ticket['pay_url'])) {
            $payButtonHtml = '
      <a href="' . e((string) $ticket['pay_url']) . '" style="display:inline-block;width:232px;height:44px;line-height:44px;border-radius:16px;background:#4ea8de;color:#071628;text-decoration:none;font-weight:700;text-align:center;font-size:15px;margin:0 6px 10px;">Plati odmah</a>';
        }

        return '
<!DOCTYPE html>
<html lang="bs">
<head>
  <meta charset="UTF-8">
  <title>Moonlight Cinema</title>
</head>
<body style="margin:0;padding:24px;background:#071628;font-family:Segoe UI,Arial,sans-serif;color:#f8f9fa;">
  <div style="max-width:640px;margin:0 auto;background:#13263b;border-radius:20px;padding:32px;border:1px solid rgba(255,255,255,0.08);">
    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-size:26px;font-weight:800;letter-spacing:0.04em;">MOONLIGHT CINEMA</div>
      <div style="margin-top:8px;color:#c4cfd4;font-size:14px;">Potvrda rezervacije i podaci za preuzimanje karte</div>
    </div>

    <div style="background:#1b3149;border-radius:16px;padding:20px;margin-bottom:20px;">
      <div style="font-size:22px;font-weight:700;margin-bottom:16px;">' . e($ticket['movie']) . '</div>
      <div style="margin-bottom:10px;color:#d7e4ef;">Datum: <strong style="color:#ffffff;">' . e($ticket['date']) . '</strong></div>
      <div style="margin-bottom:10px;color:#d7e4ef;">Vrijeme: <strong style="color:#ffffff;">' . e($ticket['time']) . '</strong></div>
      <div style="margin-bottom:10px;color:#d7e4ef;">Sala: <strong style="color:#ffffff;">' . e($ticket['hall']) . '</strong></div>
      <div style="margin-bottom:10px;color:#d7e4ef;">Sjedišta: <strong style="color:#ffffff;">' . e($seatList) . '</strong></div>
      <div style="margin-bottom:10px;color:#d7e4ef;">Status plaćanja: <strong style="color:#ffffff;">' . e($paymentStatus) . '</strong></div>
      ' . $payDeadlineHtml . '
    </div>

    ' . $qrHtml . '

    <p style="margin:0 0 18px;color:#c4cfd4;line-height:1.6;">
      Ako trebate platiti ili otkazati rezervaciju, koristite dugmad ispod. Linkovi vrijede samo za ovu rezervaciju.
    </p>

    <div style="text-align:center;margin-bottom:22px;">
      ' . $payButtonHtml . '
      <a href="' . e($ticket['cancel_url']) . '" style="display:inline-block;width:232px;height:44px;line-height:44px;border-radius:16px;background:#b30808;color:#ffffff;text-decoration:none;font-weight:700;text-align:center;font-size:15px;margin:0 6px 10px;">Otkaži rezervaciju</a>
    </div>

    <p style="margin:0;color:#9fb3c8;font-size:13px;text-align:center;">Moonlight Cinema, Mostar</p>
  </div>
</body>
</html>';
    }

}