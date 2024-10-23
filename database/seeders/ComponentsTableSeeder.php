<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Component;

class ComponentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $devices = \App\Models\Device::all();

        foreach ($devices as $device) {
            Component::factory()->count(4)->create(['device_id' => $device->id]);
        }
    }
}
