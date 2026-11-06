<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * Mailer with three transports and a persisted audit row.
 *
 * Order of preference:
 *   1. Authenticated SMTP  (MAIL_HOST configured)  – reliable on shared hosts
 *   2. PHP mail()          (no MAIL_HOST)          – local sendmail / MTA
 *   3. Log file            (APP_DEBUG=true only)   – never sends during dev
 *
 * Every attempt is recorded in `payment_emails` with its status and error, and
 * failures are reported to the caller instead of being swallowed, so a support
 * message that could not be delivered is never mistaken for one that was.
 */
class Mailer
{
    private const LOG_PATH = 'storage/logs/payment-emails.log';

    private static string $lastError = '';

    /**
     * Send $subject/$body to $to.
     *
     * In development (APP_DEBUG) the message is written to
     * storage/logs/payment-emails.log instead of leaving the server.
     *
     * @param string|null $orderId optional order reference for the audit row.
     * @param string|null $replyTo address replies should go to (the customer).
     * @return bool true when the message was delivered or logged.
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        ?int $orderId = null,
        ?string $replyTo = null
    ): bool {
        self::$lastError = '';

        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient address.';
            return false;
        }

        $subject = self::sanitizeLine($subject);
        $replyTo = $replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? trim($replyTo) : null;

        [$from, $fromName] = self::sender();
        $message = self::buildMessage($from, $fromName, $to, $subject, $body, $replyTo);

        $status = 'sent';
        $error = '';

        if (config('app.debug')) {
            $log = self::logPath() . ' [' . gmdate('c') . '] TO=' . $to . ' SUBJECT=' . $subject . PHP_EOL . $body . PHP_EOL . '---' . PHP_EOL;
            @file_put_contents(self::logPath(), $log, FILE_APPEND | LOCK_EX);
            $status = 'sent';
        } else {
            try {
                self::deliver($from, $to, $subject, $body, $message, $replyTo);
            } catch (Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
                self::$lastError = $error;
                error_log('Mail to ' . $to . ' failed: ' . $error);
            }
        }

        Database::run(
            'INSERT INTO payment_emails (order_id, recipient, subject, status, error) VALUES (?, ?, ?, ?, ?)',
            [$orderId, $to, $subject, $status, $error]
        );

        return $status === 'sent';
    }

    /** Why the last send() call failed, or '' when it succeeded. */
    public static function lastError(): string
    {
        return self::$lastError;
    }

    /**
     * Hand the message to SMTP when it is configured, otherwise to mail().
     *
     * @throws \RuntimeException
     */
    private static function deliver(
        string $from,
        string $to,
        string $subject,
        string $body,
        string $message,
        ?string $replyTo
    ): void {
        $host = trim((string) config('mail.host', ''));

        if ($host !== '') {
            $transport = new SmtpMailer(
                $host,
                max(1, (int) config('mail.port', 587)),
                trim((string) config('mail.username', '')),
                (string) config('mail.password', ''),
                strtolower(trim((string) config('mail.encryption', 'tls')))
            );
            $transport->send($from, [$to], $message);
            return;
        }

        $headers = [
            'From: ' . self::formatAddress($from, (string) config('mail.from_name', '')),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . self::rfcDate(),
        ];
        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        // -f sets the envelope sender so SPF/DKIM line up with the From header.
        $sent = @mail(
            $to,
            self::encodeHeader($subject),
            self::normalizeBody($body),
            implode("\r\n", $headers),
            '-f' . $from
        );

        if (!$sent) {
            throw new \RuntimeException('mail() returned false');
        }
    }

    /**
     * From address and display name for outgoing mail.
     *
     * @return array{0:string,1:string}
     */
    private static function sender(): array
    {
        $domain = (string) parse_url((string) config('app.url', ''), PHP_URL_HOST);
        $domain = strtolower((string) preg_replace('/^www\./i', '', $domain));
        if ($domain === '') {
            $domain = 'localhost';
        }

        $from = trim((string) config('mail.from', ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            // Never use www.* as the sender domain: no SPF record covers it and
            // receivers reject the message or file it as spam.
            $from = 'no-reply@' . $domain;
        }

        $name = trim((string) config('mail.from_name', ''));
        if ($name === '') {
            $name = Site::name();
        }
        if ($name === '') {
            $name = (string) config('app.name', 'Store');
        }

        return [$from, $name];
    }

    /** Full RFC 5322 message: headers, blank line, body. */
    private static function buildMessage(
        string $from,
        string $fromName,
        string $to,
        string $subject,
        string $body,
        ?string $replyTo
    ): string {
        $domain = substr((string) strrchr($from, '@'), 1);
        $headers = [
            'Date: ' . self::rfcDate(),
            'From: ' . self::formatAddress($from, $fromName),
            'To: ' . $to,
            'Subject: ' . self::encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . ($domain !== '' ? $domain : 'localhost') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        if ($replyTo !== null) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . self::normalizeBody($body);
    }

    private static function formatAddress(string $address, string $name): string
    {
        $name = self::sanitizeLine($name);

        return $name === '' ? $address : self::encodeHeader($name) . ' <' . $address . '>';
    }

    /** RFC 2047 encoding, only when the value is not plain ASCII. */
    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[\x80-\xFF]/', $value) !== 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /** Strip anything that could inject a header. */
    private static function sanitizeLine(string $value): string
    {
        return trim((string) preg_replace('/[\r\n]+/', ' ', $value));
    }

    private static function normalizeBody(string $body): string
    {
        return (string) preg_replace("/\r\n|\r|\n/", "\r\n", $body);
    }

    private static function rfcDate(): string
    {
        return gmdate('D, d M Y H:i:s') . ' +0000';
    }

    private static function logPath(): string
    {
        return rtrim(APP_ROOT, '/\\') . '/' . self::LOG_PATH;
    }
}
