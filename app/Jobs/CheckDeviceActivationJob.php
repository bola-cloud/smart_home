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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Fetch the device by ID
        $device = Device::find($this->deviceId);

        // If the device does not exist, return early
        if (!$device) {
            return;
        }

        // If the device has not been activated within the 1-minute window, reset its details
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
