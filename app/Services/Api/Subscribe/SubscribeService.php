<?php

namespace App\Services\Api\Subscribe;

use App\Models\Subscriber;
use Exception;
use Illuminate\Support\Facades\Log;

class SubscribeService
{
    /**
     * Subscribe email to newsletter.
     *
     * @param  string  $email
     * @return array
     */
    public function subscribe(string $email): array
    {
        try {
            $subscriber = Subscriber::firstOrCreate(['email' => $email]);

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Subscriber created successfully',
                'data'    => $subscriber,
            ];
        } catch (Exception $e) {
            Log::error('Newsletter subscription failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }
}
