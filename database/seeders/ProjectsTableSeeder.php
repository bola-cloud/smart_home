<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create projects for users
        $users = \App\Models\User::all();

        foreach ($users as $user) {
            Project::factory()->count(2)->create(['user_id' => $user->id]);
        }
    }
}
