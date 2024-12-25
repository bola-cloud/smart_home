<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;

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
        for ($i = 1; $i <= 20; $i++) {
            Device::create([
                'section_id' => null, // Nullable field
                'name' => "Device $i", // Unique name for each device
                'activation' => (bool)rand(0, 1), // Random true/false activation
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
        }
    }
}
