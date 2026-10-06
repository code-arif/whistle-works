<?php

namespace App\Services\Api\Contact;

use App\Mail\ContactSubmittedMail;
use App\Models\Contact;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            $adminEmail = config('mail.from.address');
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new ContactSubmittedMail($contact));
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
