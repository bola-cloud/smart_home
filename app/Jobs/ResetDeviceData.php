<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ResetDeviceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $device;
    public $job_uuid;

    /**
     * Create a new job instance.
     *
     * @param Device $device
     * @param string $job_uuid
     */
    public function __construct(Device $device, $job_uuid)
    {
        $this->device = $device;
        $this->job_uuid = $job_uuid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check if the job has been cancelled
        if (Cache::has("cancel-job-{$this->job_uuid}")) {
            // Job has been canceled, exit early
            return;
        }
        $this->device->refresh();
        if($this->device->activation == 0){
            // Reset device data if not confirmed within the 1-minute window
            $this->device->update([
                'section_id' => null,
                'last_updated' => null,
                'activation' => false,
                'serial' => null,
            ]);
        }
    }

    /**
     * Get the tags for the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['device:' . $this->device->id]; // Tagging the job by device ID
    }
}
