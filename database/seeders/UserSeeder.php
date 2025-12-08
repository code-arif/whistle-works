<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('local')) {

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            DB::table('model_has_permissions')->truncate();
            DB::table('model_has_roles')->truncate();
            DB::table('role_has_permissions')->truncate();
            DB::table('profiles')->truncate();
            DB::table('users')->truncate();
            DB::table('roles')->truncate();
            DB::table('permissions')->truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Insert permissions
            DB::table('permissions')->insert([
                ['id' => 1, 'name' => 'web_insert', 'guard_name' => 'web'],
                ['id' => 2, 'name' => 'web_update', 'guard_name' => 'web'],
                ['id' => 3, 'name' => 'web_delete', 'guard_name' => 'web'],
                ['id' => 4, 'name' => 'web_view', 'guard_name' => 'web'],
                ['id' => 5, 'name' => 'api_insert', 'guard_name' => 'api'],
                ['id' => 6, 'name' => 'api_update', 'guard_name' => 'api'],
                ['id' => 7, 'name' => 'api_delete', 'guard_name' => 'api'],
                ['id' => 8, 'name' => 'api_view', 'guard_name' => 'api'],
            ]);

            // Insert roles
            DB::table('roles')->insert([
                ['id' => 2, 'name' => 'admin', 'guard_name' => 'web'],
                ['id' => 4, 'name' => 'director', 'guard_name' => 'api'],
                ['id' => 5, 'name' => 'evaluator', 'guard_name' => 'api'],
                ['id' => 6, 'name' => 'referee', 'guard_name' => 'api'],
            ]);

            // Insert users (correct fields)
            DB::table('users')->insert([
                [
                    'id' => 2,
                    'first_name' => 'Admin',
                    'last_name'  => 'User',
                    'address'    => 'USA',
                    'username'   => '@admin',
                    'slug'       => 'admin',
                    'email'      => 'admin@admin.com',
                    'phone'      => '1234567890',
                    'password'   => Hash::make('12345678'),
                    'stripe_account_id' => 'acct_1RHGjbQPESrwz7hv',
                    'otp_verified_at'   => now(),
                ],
                [
                    'id' => 4,
                    'first_name' => 'Director',
                    'last_name'  => 'User',
                    'address'    => 'USA',
                    'username'   => '@director',
                    'slug'       => 'director',
                    'email'      => 'director@director.com',
                    'phone'      => '1234567891',
                    'password'   => Hash::make('12345678'),
                    'stripe_account_id' => 'acct_1RHGjbQPESrwz7hv',
                    'otp_verified_at'   => now(),
                ],
                [
                    'id' => 5,
                    'first_name' => 'Evaluator',
                    'last_name'  => 'User',
                    'address'    => 'USA',
                    'username'   => '@evaluator',
                    'slug'       => 'evaluator',
                    'email'      => 'artist@artist.com',
                    'phone'      => '1234567892',
                    'password'   => Hash::make('12345678'),
                    'stripe_account_id' => 'acct_1RHGjbQPESrwz7hv',
                    'otp_verified_at'   => now(),
                ],
                [
                    'id' => 6,
                    'first_name' => 'Referee',
                    'last_name'  => 'User',
                    'address'    => 'USA',
                    'username'   => '@referee',
                    'slug'       => 'referee',
                    'email'      => 'referee@referee.com',
                    'phone'      => '1234567893',
                    'password'   => Hash::make('12345678'),
                    'stripe_account_id' => 'acct_1RHGjbQPESrwz7hv',
                    'otp_verified_at'   => now(),
                ],
            ]);

            // Insert profiles
            DB::table('profiles')->insert([
                ['id' => 2, 'user_id' => 2, 'dob' => '2019-01-01', 'gender' => 'male'],
                ['id' => 4, 'user_id' => 4, 'dob' => '2019-01-01', 'gender' => 'male'],
            ]);

            // Insert role_has_permissions
            DB::table('role_has_permissions')->insert([
                ['permission_id' => 1, 'role_id' => 2],
                ['permission_id' => 2, 'role_id' => 2],
                ['permission_id' => 3, 'role_id' => 2],
                ['permission_id' => 4, 'role_id' => 2],
                ['permission_id' => 5, 'role_id' => 4],
                ['permission_id' => 6, 'role_id' => 4],
                ['permission_id' => 7, 'role_id' => 4],
                ['permission_id' => 8, 'role_id' => 4],
            ]);

            // Insert model_has_roles
            DB::table('model_has_roles')->insert([
                ['role_id' => 2, 'model_id' => 2, 'model_type' => 'App\Models\User'],
                ['role_id' => 4, 'model_id' => 4, 'model_type' => 'App\Models\User'],
            ]);

            // Insert model_has_permissions
            DB::table('model_has_permissions')->insert([
                ['permission_id' => 2, 'model_id' => 2, 'model_type' => 'App\Models\User'],
                ['permission_id' => 3, 'model_id' => 2, 'model_type' => 'App\Models\User'],
                ['permission_id' => 4, 'model_id' => 2, 'model_type' => 'App\Models\User'],

                ['permission_id' => 5, 'model_id' => 4, 'model_type' => 'App\Models\User'],
                ['permission_id' => 6, 'model_id' => 4, 'model_type' => 'App\Models\User'],
                ['permission_id' => 7, 'model_id' => 4, 'model_type' => 'App\Models\User'],
                ['permission_id' => 8, 'model_id' => 4, 'model_type' => 'App\Models\User'],
            ]);
        }
    }
}
