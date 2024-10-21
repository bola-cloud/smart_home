<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MembersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('members')->insert([
            [
                'user_id' => '1',
                'name' => 'John Doe',
                'email' => 'johndoe' . rand(1, 1000) . '@example.com',
                'phone_number' => '+1' . rand(1000000000, 9999999999),
                'password' => Hash::make('password123'),
                'devices' => json_encode(['3', '4']),
                'reset_code' => Str::random(6),
                'reset_code_expires_at' => Carbon::now()->addHour(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => '1',
                'name' => 'Jane Smith',
                'email' => 'janesmith' . rand(1, 1000) . '@example.com',
                'phone_number' => '+1' . rand(1000000000, 9999999999),
                'password' => Hash::make('password456'),
                'devices' => json_encode(['1', '2']),
                'reset_code' => Str::random(6),
                'reset_code_expires_at' => Carbon::now()->addHour(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
