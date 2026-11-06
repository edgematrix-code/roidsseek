<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Minimal SMTP client.
 *
 * PHP's mail() hands the message to a local sendmail binary, which many shared
 * hosts drop silently (no SPF alignment, spam filtering, disabled binary). A
 * real authenticated SMTP submission is the reliable path, so this class speaks
 * just enough SMTP to submit one message and reports every failure instead of
 * pretending the mail went out.
 *
 * Supports:
 *  - plain connections (port 25),
 *  - STARTTLS upgrade (port 587, `encryption = tls`),
 *  - implicit TLS (port 465, `encryption = ssl`),
 *  - AUTH LOGIN when a username is configured.
 *
 * No dependencies: everything here is in the PHP standard library.
 */
final class SmtpMailer
{
    private const TIMEOUT = 20;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption
    ) {
    }

    /**
     * Submit a complete RFC 5322 message.
     *
     * @param string             $from       envelope sender, already validated
     * @param array<int,string>  $recipients validated recipients
     * @param string             $message    headers and body, CRLF terminated
     *
     * @throws RuntimeException when the server refuses any step
     */
    public function send(string $from, array $recipients, string $message): void
    {
        if ($recipients === []) {
            throw new RuntimeException('No recipients.');
        }

        $socket = $this->connect();

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO ' . $this->heloName(), [250]);

            if ($this->encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP STARTTLS negotiation failed.');
                }
                $this->command($socket, 'EHLO ' . $this->heloName(), [250]);
            }

            if ($this->username !== '') {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($this->username), [334]);
                $this->command($socket, base64_encode($this->password), [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            foreach ($recipients as $recipient) {
                $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            }

            $this->command($socket, 'DATA', [354]);
            $this->write($socket, $this->dotStuff($message) . "\r\n.\r\n");
            $this->expect($socket, [250]);

            $this->command($socket, 'QUIT', [221]);
        } finally {
            @fclose($socket);
        }
    }

    /** @return resource */
    private function connect()
    {
        $host = $this->encryption === 'ssl' ? 'ssl://' . $this->host : $this->host;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $host . ':' . $this->port,
            $errno,
            $errstr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new RuntimeException(sprintf('SMTP connection to %s:%d failed (%s).', $this->host, $this->port, $errstr !== '' ? $errstr : 'error ' . $errno));
        }

        stream_set_timeout($socket, self::TIMEOUT);

        return $socket;
    }

    private function heloName(): string
    {
        $host = (string) parse_url((string) config('app.url', ''), PHP_URL_HOST);
        $host = (string) preg_replace('/^www\./i', '', $host);

        return $host !== '' ? $host : 'localhost';
    }

    /**
     * Send one command and assert the reply code.
     *
     * @param resource    $socket
     * @param array<int,int> $codes
     */
    private function command($socket, string $command, array $codes): void
    {
        $this->write($socket, $command . "\r\n");
        $this->expect($socket, $codes);
    }

    /** @param resource $socket */
    private function write($socket, string $data): void
    {
        if (@fwrite($socket, $data) === false) {
            throw new RuntimeException('SMTP write failed.');
        }
    }

    /**
     * Read a (possibly multi-line) reply and assert its code.
     *
     * @param resource    $socket
     * @param array<int,int> $codes
     */
    private function expect($socket, array $codes): void
    {
        $reply = $this->readResponse($socket);
        if (!in_array($reply['code'], $codes, true)) {
            throw new RuntimeException(sprintf('SMTP %d: %s', $reply['code'], $reply['text']));
        }
    }

    /**
     * @param resource $socket
     * @return array{code:int,text:string}
     */
    private function readResponse($socket): array
    {
        $lines = [];
        $code = 0;

        while (($line = fgets($socket, 1024)) !== false) {
            $line = rtrim($line, "\r\n");
            $lines[] = $line;
            $code = (int) substr($line, 0, 3);
            // "250-..." continues, "250 ..." ends the reply.
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        if ($lines === []) {
            throw new RuntimeException('SMTP connection closed unexpectedly.');
        }

        return ['code' => $code, 'text' => implode(' ', $lines)];
    }

    /** Escape a leading dot so the DATA terminator is unambiguous. */
    private function dotStuff(string $message): string
    {
        return (string) preg_replace('/^\./m', '..', $message);
    }
}
