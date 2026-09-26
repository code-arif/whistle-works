<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely backfills admin_fee and director_amount for existing payments.
     */
    public function up(): void
    {
        // 1. Ensure sports_types have default sports_fee if they are 0
        DB::table('sports_types')->where('sports_name', 'Football')->where('sports_fee', 0)->update(['sports_fee' => 25.00]);
        DB::table('sports_types')->where('sports_name', 'Basketball')->where('sports_fee', 0)->update(['sports_fee' => 20.00]);
        DB::table('sports_types')->where('sports_name', 'Cricket')->where('sports_fee', 0)->update(['sports_fee' => 25.00]);
        DB::table('sports_types')->where('sports_name', 'Tennis')->where('sports_fee', 0)->update(['sports_fee' => 30.00]);
        DB::table('sports_types')->where('sports_name', 'Volleyball')->where('sports_fee', 0)->update(['sports_fee' => 20.00]);
        DB::table('sports_types')->where('sports_fee', 0)->update(['sports_fee' => 25.00]);

        // 2. Load all camps with their sports_fee
        $camps = DB::table('camps')
            ->leftJoin('sports_types', 'sports_types.id', '=', 'camps.sports_type_id')
            ->select('camps.id', 'sports_types.sports_fee')
            ->get()
            ->keyBy('id');

        // 3. Backfill any payments that currently have 0 admin_fee
        $payments = DB::table('camp_payments')
            ->where('admin_fee', 0)
            ->where('amount', '>', 0)
            ->get();

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $campData = $camps->get($payment->camp_id);
            $sportsFee = $campData && (float) $campData->sports_fee > 0 ? (float) $campData->sports_fee : 25.00;

            $stripeFee = round($amount * 0.03, 2);
            $adminFee = round($sportsFee + $stripeFee, 2);

            if ($adminFee >= $amount) {
                $adminFee = round($amount * 0.20, 2);
            }

            $directorAmount = round(max(0, $amount - $adminFee), 2);

            DB::table('camp_payments')->where('id', $payment->id)->update([
                'admin_fee'       => $adminFee,
                'director_amount' => $directorAmount,
                'updated_at'      => now(),
            ]);

            if ($payment->payment_attempt_id) {
                DB::table('camp_payment_attempts')->where('id', $payment->payment_attempt_id)->update([
                    'admin_fee'       => $adminFee,
                    'director_amount' => $directorAmount,
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive rollback needed for backfill migration
    }
};
