<?php
function sendEmail($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n" .
               "Content-type:text/html;charset=UTF-8\r\n" .
               'From: noreply@gkservice.local' . "\r\n";
    $sent = @mail($to, $subject, $body, $headers);
    if ($sent) {
        return true;
    }
    // Fallback for local dev: write to backend/tmp/emails
    $dir = __DIR__ . '/../tmp/emails';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $filename = $dir . '/' . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9_\-\.]/i','_', $to) . '.html';
    $content = "Subject: " . $subject . "\n\n" . $body;
    @file_put_contents($filename, $content);
    return false;
}
