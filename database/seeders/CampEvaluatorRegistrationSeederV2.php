<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CampEvaluatorRegistration;

class CampEvaluatorRegistrationSeeder extends Seeder
{
    public function run()
    {
        $campId = 1;

        // Get evaluators
        $evaluators = User::role('evaluator', 'api')
            ->take(70)
            ->get();

        if ($evaluators->count() < 70) {
            dd("Need at least 70 evaluators.");
        }

        // Directors for approved_by
        $directors = User::role('director', 'api')->pluck('id');

        $approvedCount = 30;
        $pendingCount  = 20;
        $rejectedCount = 20;

        $index = 0;

        foreach ($evaluators as $evaluator) {

            // determine status
            if ($index < $approvedCount) {
                $status = 'approved';
            } elseif ($index < ($approvedCount + $pendingCount)) {
                $status = 'pending';
            } else {
                $status = 'rejected';
            }

            CampEvaluatorRegistration::updateOrCreate(
                [
                    'camp_id'      => $campId,
                    'evaluator_id' => $evaluator->id,
                ],
                [
                    'status' => $status,
                    'registration_note' => 'Seeder generated data',

                    'approved_at' => $status === 'approved' ? now() : null,
                    'approved_by' => $status === 'approved'
                        ? $directors->random()
                        : null,

                    'rejected_at' => $status === 'rejected' ? now() : null,
                    'rejection_reason' => $status === 'rejected'
                        ? 'Seeder auto rejected'
                        : null,

                    'can_view_own_evaluations' => true,
                ]
            );

            $index++;
        }

        dump("70 evaluator registrations created (30 approved, 20 pending, 20 rejected)");
    }
}
