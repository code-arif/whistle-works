<?php

namespace App\Helpers;

use App\Models\User;

class Notify
{
    public static function Firebase(string $title, string $body, $user_id)
    {
        $user = User::find($user_id);
        if ($user && $user->firebaseTokens) {
            $notifyData = ['title' => $title, 'body'  => $body, 'icon'  => env('APP_LOGO')];
            foreach ($user->firebaseTokens as $firebaseToken) {
                Helper::sendNotifyMobile($firebaseToken->token, $notifyData);
            }
        }
    }
}

