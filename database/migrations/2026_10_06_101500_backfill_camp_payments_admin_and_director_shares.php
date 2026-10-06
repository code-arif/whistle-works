<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely backfills admin_fee and director_amount for historical payment records in production.
     */
    public function up(): void
    {
        // 1. Ensure sports_types have a default fee if set to 0 or null
        DB::table('sports_types')
            ->where('sports_fee', '<=', 0)
            ->orWhereNull('sports_fee')
            ->update(['sports_fee' => 20.00]);

        // 2. Pre-fetch camps with their configured sports fee
        $camps = DB::table('camps')
            ->leftJoin('sports_types', 'camps.sports_type_id', '=', 'sports_types.id')
            ->select('camps.id', 'camps.price', 'sports_types.sports_fee')
            ->get()
            ->keyBy('id');

        // 3. Safely backfill only payments where admin_fee or director_amount is missing (0 or null)
        DB::table('camp_payments')
            ->where(function ($query) {
                $query->where('admin_fee', '<=', 0)
                    ->orWhereNull('admin_fee')
                    ->orWhere('director_amount', '<=', 0)
                    ->orWhereNull('director_amount');
            })
            ->where('amount', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($camps) {
                foreach ($payments as $payment) {
                    $campData = $camps->get($payment->camp_id);
                    $sportsFee = ($campData && (float)$campData->sports_fee > 0)
                        ? (float)$campData->sports_fee
                        : 20.00;

                    $amount = (float)$payment->amount;
                    $stripeFee = round($amount * 0.03, 2);
                    $adminFee = round($sportsFee + $stripeFee, 2);

                    // Safety guard: admin fee cannot exceed gross amount
                    if ($adminFee >= $amount) {
                        $adminFee = round($amount * 0.10, 2); // Fallback 10% platform fee
                    }

                    $directorAmount = max(0, round($amount - $adminFee, 2));

                    DB::table('camp_payments')
                        ->where('id', $payment->id)
                        ->update([
                            'admin_fee'       => $adminFee,
                            'director_amount' => $directorAmount,
                            'updated_at'      => now(),
                        ]);

                    if ($payment->payment_attempt_id) {
                        DB::table('camp_payment_attempts')
                            ->where('id', $payment->payment_attempt_id)
                            ->update([
                                'admin_fee'       => $adminFee,
                                'director_amount' => $directorAmount,
                                'updated_at'      => now(),
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op: historical data backfills do not need reversal
    }
};
