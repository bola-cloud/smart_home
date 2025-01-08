<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\Component;

class DeviceComponentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Fetch all devices with device_type_id = 16
        $devices = Device::where('device_type_id', 16)->get();

        foreach ($devices as $device) { 
            // Count existing components for the device
            $existingComponentsCount = $device->components()->count();

            // If the device has less than 6 components, create the missing ones
            if ($existingComponentsCount < 6) {
                for ($i = $existingComponentsCount + 1; $i <= 6; $i++) {
                    Component::create([
                        'device_id' => $device->id, // Relate to the device
                        'name' => "Dummy Component $i for Device {$device->id}", // Dummy name
                        'type' => "Type $i", // Example type
                        'order' => $i, // Order of the component
                        'image_id' => null, // Nullable field
                        'file_path' => null, // Nullable field
                        'manual' => false, // Default value
                    ]);
                }
            }
        }
    }
}
