<?php

namespace App\Services\Api\Legal;

use App\Models\PrivecyAndTerms;

class PrivacyPolicyService
{
    /**
     * Get privacy policy content.
     *
     * @return PrivecyAndTerms|null
     */
    public function getPrivacyPolicy(): ?PrivecyAndTerms
    {
        return PrivecyAndTerms::where('type', 'privacy')->first();
    }

    /**
     * Get terms and conditions content.
     *
     * @return PrivecyAndTerms|null
     */
    public function getTermsAndConditions(): ?PrivecyAndTerms
    {
        return PrivecyAndTerms::where('type', 'terms')->first();
    }
}
