<?php

namespace Database\Factories;

use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponentFactory extends Factory
{
    protected $model = Component::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['Sensor', 'Actuator']),
            'order' => $this->faker->randomDigitNotNull,
            'device_id' => \App\Models\Device::factory(),  // Assuming the component belongs to a device
        ];
    }
}
