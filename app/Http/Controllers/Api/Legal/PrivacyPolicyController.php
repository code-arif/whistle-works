<?php

namespace App\Http\Controllers\Api\Legal;

use App\Http\Controllers\Controller;
use App\Services\Api\Legal\PrivacyPolicyService;
use Illuminate\Http\JsonResponse;

class PrivacyPolicyController extends Controller
{
    protected PrivacyPolicyService $privacyPolicyService;

    public function __construct(PrivacyPolicyService $privacyPolicyService)
    {
        $this->privacyPolicyService = $privacyPolicyService;
    }

    /**
     * Display a listing of the privacy policies.
     *
     * @return JsonResponse
     */
    public function privecyPolicy(): JsonResponse
    {
        $data = $this->privacyPolicyService->getPrivacyPolicy();

        return response()->json([
            'status'  => true,
            'message' => 'Privacy policy fetched successfully',
            'data'    => $data,
        ]);
    }

    /**
     * Display a listing of the terms and conditions.
     *
     * @return JsonResponse
     */
    public function termsAndConditions(): JsonResponse
    {
        $data = $this->privacyPolicyService->getTermsAndConditions();

        return response()->json([
            'status'  => true,
            'message' => 'Terms and conditions fetched successfully',
            'data'    => $data,
        ]);
    }
}
