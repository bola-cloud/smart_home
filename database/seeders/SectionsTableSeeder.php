<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Section;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $projects = \App\Models\Project::all();

        foreach ($projects as $project) {
            Section::factory()->count(2)->create(['project_id' => $project->id]);
        }
    }
}
