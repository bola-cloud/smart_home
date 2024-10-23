<?php

namespace Database\Factories;

use App\Models\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceTypeFactory extends Factory
{
    protected $model = DeviceType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
