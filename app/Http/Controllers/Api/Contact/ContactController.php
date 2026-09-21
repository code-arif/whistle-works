<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Contact\SubmitContactRequest;
use App\Services\Api\Contact\ContactService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    use ApiResponse;

    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Submit contact form.
     *
     * @param  SubmitContactRequest  $request
     * @return JsonResponse
     */
    public function submitContact(SubmitContactRequest $request): JsonResponse
    {
        $result = $this->contactService->submitContact($request->validated());

        if (!$result['success']) {
            return $this->error($result['data'], $result['message'], $result['code']);
        }

        return $this->success($result['message'], $result['data'], $result['code']);
    }
}
