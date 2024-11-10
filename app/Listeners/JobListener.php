<?php

// In App\Listeners\JobListener.php

namespace App\Listeners;

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use App\Models\JobTracker;
use Illuminate\Support\Facades\Log;

class JobListener
{
    public function handleJobQueued(JobQueued $event)
    {
        // Retrieve the job ID and condition ID
        $jobId = $event->id;
        $job = $event->job;

        // Ensure the job instance has a condition ID
        $conditionId = method_exists($job, 'getConditionId') ? $job->getConditionId() : null;

        // Only insert if condition_id is not null
        if ($conditionId !== null) {
            JobTracker::create([
                'job_id' => $jobId,
                'condition_id' => $conditionId,
            ]);
            Log::info("Job queued: Job ID {$jobId}, Condition ID {$conditionId}");
        } else {
            Log::warning("Job queued without a condition ID: Job ID {$jobId}");
        }
    }

    public function handleJobProcessed(JobProcessed $event)
    {
        $jobId = $event->job->getJobId();

        Log::info("Job processed: Job ID {$jobId}");

        JobTracker::where('job_id', $jobId)->update(['status' => 'processed']);
    }

    public function handleJobFailed(JobFailed $event)
    {
        $jobId = $event->job->getJobId();

        Log::error("Job failed: Job ID {$jobId}, Exception: {$event->exception->getMessage()}");

        JobTracker::where('job_id', $jobId)->update(['status' => 'failed']);
    }
}
