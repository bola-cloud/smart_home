<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDeviceActivationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deviceId;

    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    public function handle()
    {
        $device = Device::find($this->deviceId);

        // Return early if the device doesn't exist
        if (!$device) {
            return;
        }

        // Reset device details if it hasn't been activated within the 1-minute window
        if ($device->activation == 0) {
            $device->update([
                'section_id' => null,
                'last_updated' => null,
                'serial' => null,
                'user_id' => null,
            ]);
        }
    }
}