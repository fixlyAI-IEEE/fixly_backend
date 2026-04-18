<?php
namespace App\Services;

use Twilio\Rest\Client;

class SmsService
{
    public function send($phone, $message)
    {
        $client = new Client(
            env('TWILIO_SID'),
            env('TWILIO_TOKEN')
        );

        return $client->messages->create(
            $phone,
            [
                'from' => env('TWILIO_FROM'),
                'body' => $message
            ]
        );
    }
}