<?php

namespace App\Utils;

class Mailler
{
    public static function send(string $to, string $subject, string $message): bool
    {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: no-reply@kitismart.com' . "\r\n";

        return mail($to, $subject, $message, $headers);
    }
}