<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Send a plain-text UTF-8 email over SMTP.
 *
 * Minimal, dependency-free client: no auth, no TLS — sufficient for the dev
 * Mailpit catcher and for a localhost MTA. A production host with an
 * authenticated/TLS relay will need this extended (tracked for the deploy
 * phase). Returns true on a 250 acceptance.
 */
function send_mail(string $to, string $subject, string $body): bool
{
    $cfg = df_config()['mail'];

    $fp = @fsockopen($cfg['host'], $cfg['port'], $errno, $errstr, 10);
    if (!$fp) {
        error_log("send_mail: SMTP connect failed: {$errstr} ({$errno})");
        return false;
    }
    stream_set_timeout($fp, 10);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Last line of a (possibly multiline) reply has a space at col 4.
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = static function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };

    $read();                       // server greeting
    $cmd('EHLO desirable-futures');
    $cmd('MAIL FROM:<' . $cfg['from'] . '>');
    $cmd('RCPT TO:<' . $to . '>');

    if (strncmp($cmd('DATA'), '354', 3) !== 0) {
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        error_log('send_mail: DATA not accepted');
        return false;
    }

    $headers = implode("\r\n", [
        'From: ' . mb_encode_mimeheader($cfg['from_name']) . ' <' . $cfg['from'] . '>',
        'To: <' . $to . '>',
        'Subject: ' . mb_encode_mimeheader($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date('r'),
    ]);

    $normalised = str_replace(["\r\n", "\r", "\n"], "\r\n", $body);
    $message = $headers . "\r\n\r\n" . $normalised;
    $message = preg_replace('/(^|\r\n)\./', '$1..', $message); // dot-stuffing

    fwrite($fp, $message . "\r\n.\r\n");
    $resp = $read();
    $cmd('QUIT');
    fclose($fp);

    return strncmp($resp, '250', 3) === 0;
}
