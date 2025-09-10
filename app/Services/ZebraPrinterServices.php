<?php

namespace App\Services;

class ZebraPrinterServices
{
    public static function sendPrnToPrinter(string $template, string $printerIp, int $printerPort = 9100): bool
    {
        $data = $template;

        $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 30);
        if (!$fp) {
            throw new \Exception("Could not connect to printer: $errstr ($errno)");
        }

        fwrite($fp, $data);
        fclose($fp);

        return true;
    }
}
