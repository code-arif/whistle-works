<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\CampRefereeJearsyNumber;
use App\Models\User;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class RefereeCheckinStatusTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'get_referee_checkin_status';
    }

    public function getDescription(): string
    {
        return 'Check the registration and physical check-in status of a referee for a specific camp. Returns registration date (registered_at), check-in date (checked_in_at), status, jersey number, and referee profile details.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'referee_id' => [
                    'type' => 'integer',
                    'description' => 'User ID or Person ID of the referee (e.g. 84)',
                ],
                'referee_name' => [
                    'type' => 'string',
                    'description' => 'Full name or username of the referee',
                ],
                'camp_id' => [
                    'type' => 'integer',
                    'description' => 'Camp ID (e.g. 2)',
                ],
                'camp_name' => [
                    'type' => 'string',
                    'description' => 'Name or title of the camp',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $refereeId = $arguments['referee_id'] ?? null;
        $refereeName = $arguments['referee_name'] ?? null;
        $campId = $arguments['camp_id'] ?? null;
        $campName = $arguments['camp_name'] ?? null;

        // Find Referee
        $user = null;
        if ($refereeId) {
            $user = User::find($refereeId);
        } elseif (!empty($refereeName)) {
            $trimmed = trim($refereeName);
            $user = User::where(function ($q) use ($trimmed) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$trimmed}%"])
                    ->orWhere('first_name', 'LIKE', "%{$trimmed}%")
                    ->orWhere('last_name', 'LIKE', "%{$trimmed}%")
                    ->orWhere('username', 'LIKE', "%{$trimmed}%");
            })->first();
        }

        // Find Camp
        $camp = null;
        if ($campId) {
            $camp = Camp::find($campId);
        } elseif (!empty($campName)) {
            $camp = Camp::where('camp_name', 'LIKE', "%{$campName}%")->first();
        }

        // Query check-in record
        $query = CampRefereeCheckin::with(['referee', 'camp']);

        if ($user) {
            $query->where('referee_id', $user->id);
        }
        if ($camp) {
            $query->where('camp_id', $camp->id);
        }

        $checkins = $query->get();

        if ($checkins->isEmpty()) {
            return [
                'result' => [
                    'found' => false,
                    'message' => "No registration or check-in record found for " . ($user ? "Referee #{$user->id} ({$user->first_name} {$user->last_name})" : "specified referee") . " in " . ($camp ? "Camp #{$camp->id} ({$camp->camp_name})" : "the specified camp") . ".",
                ],
                'widget_type' => null,
                'widget_payload' => null,
                'summary' => 'Referee not registered or checked in.',
            ];
        }

        $details = [];
        foreach ($checkins as $c) {
            $ref = $c->referee;
            $cmp = $c->camp;

            $jersey = CampRefereeJearsyNumber::where('camp_id', $c->camp_id)
                ->where('referee_id', $c->referee_id)
                ->value('jersey_number');

            $fullName = $ref ? trim("{$ref->first_name} {$ref->last_name}") : "Referee #{$c->referee_id}";
            $campTitle = $cmp ? ($cmp->camp_name ?? "Camp #{$cmp->id}") : "Camp #{$c->camp_id}";

            $details[] = [
                'referee_id' => $c->referee_id,
                'referee_name' => $fullName,
                'email' => $ref?->email,
                'phone' => $ref?->phone,
                'camp_id' => $c->camp_id,
                'camp_name' => $campTitle,
                'registration_status' => $c->registration_status,
                'registered_at' => $c->registered_at ? $c->registered_at->format('Y-m-d H:i:s') : ($c->created_at ? $c->created_at->format('Y-m-d H:i:s') : 'N/A'),
                'checked_in_at' => $c->checked_in_at ? $c->checked_in_at->format('Y-m-d H:i:s') : ($c->registration_status === 'checked_in' ? 'Checked in (date unrecorded)' : 'Not checked in yet'),
                'jersey_number' => $jersey ?: 'N/A',
            ];
        }

        $widgetPayload = [
            'type' => 'table',
            'title' => 'Referee Camp Registration & Check-in Details',
            'headers' => ['Referee', 'Camp', 'Status', 'Registered Date', 'Checked-in Date', 'Jersey #'],
            'rows' => array_map(function ($d) {
                return [
                    'referee' => $d['referee_name'],
                    'camp' => $d['camp_name'],
                    'status' => ucfirst($d['registration_status']),
                    'registered' => $d['registered_at'],
                    'checked_in' => $d['checked_in_at'],
                    'jersey' => $d['jersey_number'],
                ];
            }, $details),
        ];

        return [
            'result' => [
                'found' => true,
                'count' => count($details),
                'checkin_records' => $details,
            ],
            'widget_type' => 'table',
            'widget_payload' => $widgetPayload,
            'summary' => "Referee " . $details[0]['referee_name'] . " is " . $details[0]['registration_status'] . " for " . $details[0]['camp_name'] . ". Registered: " . $details[0]['registered_at'] . ", Checked-in: " . $details[0]['checked_in_at'] . ".",
        ];
    }
}
