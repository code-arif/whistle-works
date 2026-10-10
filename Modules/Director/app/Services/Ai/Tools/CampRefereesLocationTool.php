<?php

namespace Modules\Director\app\Services\Ai\Tools;

use App\Models\CampRefereeJearsyNumber;
use App\Models\User;
use Modules\Director\app\Services\Ai\Contracts\AiToolInterface;
use Modules\Director\Models\Camp;
use Modules\Director\Models\CampRefereeCheckin;

class CampRefereesLocationTool implements AiToolInterface
{
    public function getName(): string
    {
        return 'get_camp_referees_by_location';
    }

    public function getDescription(): string
    {
        return 'Find referees attending a specific camp who live in a specific city, state, or region (e.g. "Tulsa, OK"). Returns referee names, contact information, jersey numbers, and check-in status.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'camp_name' => [
                    'type' => 'string',
                    'description' => 'Name or title of the camp (e.g. "Camp B", "Summer Elite Camp")',
                ],
                'camp_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the camp if known',
                ],
                'city' => [
                    'type' => 'string',
                    'description' => 'City name (e.g. "Tulsa", "Dallas")',
                ],
                'state' => [
                    'type' => 'string',
                    'description' => 'State name or 2-letter abbreviation (e.g. "OK", "Oklahoma", "TX")',
                ],
                'location_query' => [
                    'type' => 'string',
                    'description' => 'Full location string if provided (e.g. "Tulsa, OK")',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments, int $userId): array
    {
        $campId = $arguments['camp_id'] ?? null;
        $campName = $arguments['camp_name'] ?? null;
        $city = $arguments['city'] ?? null;
        $state = $arguments['state'] ?? null;
        $locationQuery = $arguments['location_query'] ?? null;

        // Parse location query if city/state not explicitly separated
        if (!empty($locationQuery) && empty($city) && empty($state)) {
            $parts = explode(',', $locationQuery);
            $city = trim($parts[0] ?? '');
            $state = trim($parts[1] ?? '');
        }

        // Find Camp
        $camp = null;
        if ($campId) {
            $camp = Camp::find($campId);
        } elseif (!empty($campName)) {
            $camp = Camp::where('camp_name', 'LIKE', "%{$campName}%")->first();
        }

        // Fetch checkins for this camp or all camps if camp not specified
        $checkinQuery = CampRefereeCheckin::with(['referee']);
        if ($camp) {
            $checkinQuery->where('camp_id', $camp->id);
        }

        $checkins = $checkinQuery->get();

        $matchingReferees = [];

        foreach ($checkins as $checkin) {
            $referee = $checkin->referee;
            if (!$referee) {
                continue;
            }

            $address = (string) ($referee->address ?? '');

            // Check if address matches search filters
            $matches = true;

            if (!empty($city)) {
                if (stripos($address, $city) === false) {
                    $matches = false;
                }
            }

            if (!empty($state)) {
                if (stripos($address, $state) === false) {
                    $matches = false;
                }
            }

            if (!empty($locationQuery) && empty($city) && empty($state)) {
                if (stripos($address, $locationQuery) === false) {
                    $matches = false;
                }
            }

            if ($matches) {
                // Fetch jersey number
                $jersey = CampRefereeJearsyNumber::where('camp_id', $checkin->camp_id)
                    ->where('referee_id', $referee->id)
                    ->value('jersey_number');

                $fullName = trim("{$referee->first_name} {$referee->last_name}") ?: $referee->username;

                $matchingReferees[] = [
                    'referee_id' => $referee->id,
                    'name' => $fullName,
                    'email' => $referee->email,
                    'phone' => $referee->phone,
                    'address' => $address,
                    'jersey_number' => $jersey ?: 'N/A',
                    'registration_status' => $checkin->registration_status,
                    'camp_id' => $checkin->camp_id,
                    'camp_name' => $camp ? ($camp->camp_name ?? $camp->name) : 'Camp #' . $checkin->camp_id,
                ];
            }
        }

        $locLabel = trim("{$city}, {$state}", ', ') ?: ($locationQuery ?: 'the specified location');
        $campLabel = $camp ? ($camp->camp_name ?? $camp->name) : 'the camp';

        $widgetPayload = [
            'type' => 'table',
            'title' => "Referees at {$campLabel} living in {$locLabel}",
            'headers' => ['Name', 'Jersey #', 'Address / Location', 'Status', 'Email', 'Phone'],
            'rows' => array_map(function ($r) {
                return [
                    'name' => $r['name'],
                    'jersey' => $r['jersey_number'],
                    'address' => $r['address'] ?: 'N/A',
                    'status' => ucfirst($r['registration_status'] ?? 'registered'),
                    'email' => $r['email'],
                    'phone' => $r['phone'] ?: 'N/A',
                ];
            }, $matchingReferees),
        ];

        return [
            'result' => [
                'camp' => $campLabel,
                'location_filter' => $locLabel,
                'count' => count($matchingReferees),
                'referees' => $matchingReferees,
            ],
            'widget_type' => 'table',
            'widget_payload' => $widgetPayload,
            'summary' => "Found " . count($matchingReferees) . " referees at {$campLabel} from {$locLabel}.",
        ];
    }
}
