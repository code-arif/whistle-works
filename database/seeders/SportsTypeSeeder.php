<?php

namespace Database\Seeders;

use App\Models\SportsType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SportsTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SportsType::insert([
            [
                'sports_name' => 'Football',
                'sports_fee'  => 25.00,
                'icon'        => 'icons/football.png',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'sports_name' => 'Basketball',
                'sports_fee'  => 20.00,
                'icon'        => 'icons/basketball.png',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'sports_name' => 'Cricket',
                'sports_fee'  => 25.00,
                'icon'        => 'icons/cricket.png',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'sports_name' => 'Tennis',
                'sports_fee'  => 30.00,
                'icon'        => 'icons/tennis.png',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'sports_name' => 'Volleyball',
                'sports_fee'  => 20.00,
                'icon'        => 'icons/volleyball.png',
                'status'      => 'active',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
