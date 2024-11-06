<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;

class CheckDeviceActivation extends Command
{
    protected $signature = 'check:device-activation {device_id}';
    protected $description = 'Check if the device activation is 0 after a minute and reset columns if not activated';

    public function handle()
    {
        // Get the device by the passed device_id
        $device = Device::find($this->argument('device_id'));

        if (!$device) {
            $this->error('Device not found.');
            return;
        }

        // Check if the device is activated
        if ($device->activation == 0) {
            // If not activated, reset the relevant fields
            $device->update([
                'section_id' => null,
                'last_updated' => null,
                'serial' => null,
            ]);

            $this->info('Device has not been activated, fields have been reset.');
        } else {
            $this->info('Device is already activated, no changes made.');
        }
    }
}
