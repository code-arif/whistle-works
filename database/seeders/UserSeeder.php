<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('profiles')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /* =======================
         * Permissions
         * ======================= */
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'web_insert', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'web_update', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'web_delete', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'web_view',   'guard_name' => 'web'],
            ['id' => 5, 'name' => 'api_insert', 'guard_name' => 'api'],
            ['id' => 6, 'name' => 'api_update', 'guard_name' => 'api'],
            ['id' => 7, 'name' => 'api_delete', 'guard_name' => 'api'],
            ['id' => 8, 'name' => 'api_view',   'guard_name' => 'api'],
        ]);

        /* =======================
         * Roles
         * ======================= */
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin',     'guard_name' => 'web'],
            ['id' => 2, 'name' => 'director',  'guard_name' => 'api'],
            ['id' => 3, 'name' => 'evaluator', 'guard_name' => 'api'],
            ['id' => 4, 'name' => 'referee',   'guard_name' => 'api'],
        ]);

        $users = [];
        $modelRoles = [];
        $id = 1;

        /* =======================
         * Admin (1)
         * ======================= */
        $users[] = [
            'id' => $id,
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'address'    => 'USA',
            'username'   => '@admin',
            'slug'       => 'admin',
            'email'      => 'admin@admin.com',
            'phone'      => '1000000000',
            'password'   => Hash::make('12345678'),
            'stripe_account_id' => 'acct_test_admin',
            'otp_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $modelRoles[] = [
            'role_id' => 1,
            'model_id' => $id,
            'model_type' => 'App\Models\User',
        ];

        $id++;

        /* =======================
         * Directors (5)
         * ======================= */
        for ($i = 1; $i <= 5; $i++) {
            $users[] = [
                'id' => $id,
                'first_name' => "Director{$i}",
                'last_name'  => 'User',
                'address'    => 'USA',
                'username'   => "@director{$i}",
                'slug'       => "director-{$i}",
                'email'      => "director{$i}@example.com",
                'phone'      => '200000000' . $i,
                'password'   => Hash::make('12345678'),
                'stripe_account_id' => 'acct_test_director',
                'otp_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $modelRoles[] = [
                'role_id' => 2,
                'model_id' => $id,
                'model_type' => 'App\Models\User',
            ];

            $id++;
        }

        /* =======================
         * Evaluators (20)
         * ======================= */
        for ($i = 1; $i <= 40; $i++) {
            $users[] = [
                'id' => $id,
                'first_name' => "Evaluator{$i}",
                'last_name'  => 'User',
                'address'    => 'USA',
                'username'   => "@evaluator{$i}",
                'slug'       => "evaluator-{$i}",
                'email'      => "evaluator{$i}@example.com",
                'phone'      => '300000000' . $i,
                'password'   => Hash::make('12345678'),
                'stripe_account_id' => 'acct_test_evaluator',
                'otp_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $modelRoles[] = [
                'role_id' => 3,
                'model_id' => $id,
                'model_type' => 'App\Models\User',
            ];

            $id++;
        }

        /* =======================
         * Referees (20)
         * ======================= */
        for ($i = 1; $i <= 50; $i++) {
            $users[] = [
                'id' => $id,
                'first_name' => "Referee{$i}",
                'last_name'  => 'User',
                'address'    => 'USA',
                'username'   => "@referee{$i}",
                'slug'       => "referee-{$i}",
                'email'      => "referee{$i}@example.com",
                'phone'      => '400000000' . $i,
                'password'   => Hash::make('12345678'),
                'stripe_account_id' => 'acct_test_referee',
                'otp_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $modelRoles[] = [
                'role_id' => 4,
                'model_id' => $id,
                'model_type' => 'App\Models\User',
            ];

            $id++;
        }

        /* =======================
         * Insert Data
         * ======================= */
        DB::table('users')->insert($users);
        DB::table('model_has_roles')->insert($modelRoles);
    }
}
