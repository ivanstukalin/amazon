<?php

namespace App\Common;

class AbstractLogger
{
    final static public function log(string $message): void
    {
        $dateTime = new \DateTime();
        file_put_contents('../../tmp/log.txt', "{$dateTime->format('Y-m-d h:m:s')} - {$message}");
    }
}