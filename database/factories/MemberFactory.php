<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition()
    {
        return [
            'owner_id' => User::factory(),  // Creates a new User as the owner
            'member_id' => User::factory(), // Creates a new User as the member
            'project_id' => Project::factory(), // Creates a new Project associated with the member
            'devices' => [
                // Sample device permissions format
                "1" => [
                    "2" => "view",
                    "3" => "control",
                ],
                "4" => [
                    "5" => "view",
                ],
            ],  // JSON structure for devices and permissions
        ];
    }
}
