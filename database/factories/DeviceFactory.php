<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'serial' => null,
            'activation' => 0,
            'last_updated' => null,
            'section_id' =>null,  // Assuming the device belongs to a section
            'device_type_id' => \App\Models\DeviceType::factory(),  // Assuming the device has a type
        ];
    }
}

