<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioTestController extends Controller
{
    /**
     * Send test SMS to multiple phone numbers.
     *
     * Expected Payload (JSON or Form Data in Postman):
     * {
     *     "phone_numbers": ["+1234567890", "+0987654321"],
     *     "message": "This is a test message from Twilio!"
     * }
     */
    public function sendTestSms(Request $request)
    {
        $request->validate([
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
            'message' => 'required|string'
        ]);

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            return response()->json([
                'success' => false,
                'message' => 'Twilio configuration is missing. Please check your .env file.',
                'config_debug' => [
                    'has_sid' => !empty($sid),
                    'has_token' => !empty($token),
                    'has_from' => !empty($from),
                ]
            ], 500);
        }

        $client = new Client($sid, $token);
        $results = [];

        foreach ($request->phone_numbers as $number) {
            try {
                $twilioMessage = $client->messages->create($number, [
                    'from' => $from,
                    'body' => $request->message,
                ]);

                $results[] = [
                    'phone' => $number,
                    'status' => 'success',
                    'sid' => $twilioMessage->sid
                ];
            } catch (\Exception $e) {
                Log::error('Twilio Test SMS failed for ' . $number . ': ' . $e->getMessage());
                $results[] = [
                    'phone' => $number,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Test SMS processing completed.',
            'results' => $results
        ]);
    }
}
