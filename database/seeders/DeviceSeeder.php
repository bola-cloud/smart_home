<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use App\Models\Component;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Loop to create 20 devices
        for ($i = 1; $i <= 50; $i++) {
            // Create the device
            $device = Device::create([
                'section_id' => null, // Nullable field
                'name' => "Device $i", // Unique name for each device
                'activation' => 0, // Random true/false activation
                'last_updated' => null, // Nullable field
                'device_type_id' => 16, // Default value
                'serial' => null, // Nullable field
                'user_id' => null, // Nullable field
                'cancelled' => false, // Default to not cancelled
                'ip' => "192.168.1." . rand(100, 199), // Random IP address
                'mac_address' => sprintf(
                    '%02X:%02X:%02X:%02X:%02X:%02X',
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255)
                ), // Random MAC address
            ]);
    
            // Create 4 related components for each device
            for ($j = 1; $j <= 6; $j++) {
                Component::create([
                    'device_id' => $device->id, // Relate to the device
                    'name' => "Component $j for Device $i", // Unique name for each component
                    'type' => "Type $j", // Example type (modify as needed)
                    'order' => $j, // Order of the component
                    'image_id' => null, // Nullable field
                    'file_path' => null, // Nullable field
                    'manual' => false, // Default value
                ]);
            }
        }
    }
    
}
