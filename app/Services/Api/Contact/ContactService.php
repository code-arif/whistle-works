<?php

namespace App\Services\Api\Contact;

use App\Mail\ContactSubmittedMail;
use App\Models\Contact;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactService
{
    /**
     * Store contact submission and queue notification email to admin/support.
     *
     * @param  array  $data
     * @return array
     */
    public function submitContact(array $data): array
    {
        DB::beginTransaction();

        try {
            $contact = Contact::create($data);

            $setting = Setting::first();
            $adminEmail = !empty($setting?->email)
                ? $setting->email
                : (!empty($setting?->email) ? $setting->email : (config('mail.from.address') ?: env('ADMIN_MAIL')));

            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->queue(new ContactSubmittedMail($contact));
                } catch (Throwable $mailError) {
                    Log::warning('Contact notification email dispatch failed: ' . $mailError->getMessage(), [
                        'contact_id'  => $contact->id,
                        'email' => $adminEmail,
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'code'    => 201,
                'message' => 'Contact form submitted successfully!',
                'data'    => $contact,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Contact form submit failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);

            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Something went wrong. Please try again later.',
                'data'    => [],
            ];
        }
    }
}
