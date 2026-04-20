<?php
namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send($phone, $message)
    {
           Log::info('SMS (dev only)', [
            'to'      => $phone,
            'message' => $message,
        ]);

        return true;

    }
}