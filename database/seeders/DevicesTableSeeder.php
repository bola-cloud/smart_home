<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;

class DevicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $sections = \App\Models\Section::all();

        foreach ($sections as $section) {
            Device::factory()->count(3)->create(['section_id' => $section->id]);
        }
    }
}
