<?php

namespace App\Services\Admin;

use App\Models\PrivecyAndTerms;
use Exception;

class TermsPrivacyService
{
    /**
     * Fetch formatted Terms & Privacy content.
     */
    public function getTermsAndPrivacyData(): array
    {
        $privacy = PrivecyAndTerms::where('type', 'privacy')->first();
        $terms   = PrivecyAndTerms::where('type', 'terms')->first();

        return [
            'privacy' => [
                'content'    => $privacy?->description ?? '',
                'updated_at' => $privacy?->updated_at ? $privacy->updated_at->format('M d, Y • h:i A') : 'Not yet configured',
            ],
            'terms' => [
                'content'    => $terms?->description ?? '',
                'updated_at' => $terms?->updated_at ? $terms->updated_at->format('M d, Y • h:i A') : 'Not yet configured',
            ],
        ];
    }

    /**
     * Update or create document content by type ('privacy' or 'terms').
     */
    public function updateContent(string $type, ?string $description): PrivecyAndTerms
    {
        $normalizedType = strtolower($type);
        if (!in_array($normalizedType, ['privacy', 'terms'])) {
            throw new Exception("Invalid document type: {$type}");
        }

        return PrivecyAndTerms::updateOrCreate(
            ['type' => $normalizedType],
            ['description' => $description ?? '']
        );
    }
}
