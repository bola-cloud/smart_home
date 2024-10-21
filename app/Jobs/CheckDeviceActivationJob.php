<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Device;

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

        if (!$device) {
            return;
        }

        if ($device->activation == 0) {
            $device->update([
                'section_id' => null,
                'last_updated' => null,
                'serial' => null,
            ]);
        }
    }
}
