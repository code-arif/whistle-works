<?php

namespace App\Traits;

use Twilio\Rest\Client;

trait SMS
{
    public function twilioSms($to, $message)
    {
        $sid = env('TWILIO_SID', config('services.twilio.sid', getenv("TWILIO_ACCOUNT_SID")));
        $token = env('TWILIO_TOKEN', config('services.twilio.token', getenv("TWILIO_AUTH_TOKEN")));
        $from = env('TWILIO_FROM', config('services.twilio.from', getenv("TWILIO_FROM_NUMBER")));

        $twilio = new Client($sid, $token);
        $message = $twilio->messages
            ->create(
                $to, // to
                array(
                    "from" => $from,
                    "body" => $message
                )
            );
    }
}
