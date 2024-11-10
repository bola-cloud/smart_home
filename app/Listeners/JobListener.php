<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use App\Models\JobTracker;
use Illuminate\Support\Facades\Log;

class JobListener
{
    /**
     * Handle the JobQueued event.
     *
     * @param  JobQueued  $event
     * @return void
     */
    public function handleJobQueued(JobQueued $event)
    {
        // Get the job's unique ID and any custom properties for tracking
        $jobId = $event->id;
        $job = $event->job;

        // Check if the job has a condition ID (assuming this is set in the job's constructor)
        $conditionId = method_exists($job, 'getConditionId') ? $job->getConditionId() : null;

        // Track the job in the JobTracker model
        JobTracker::create([
            'job_id' => $jobId,
            'condition_id' => $conditionId,
        ]);

        Log::info("Job queued: Job ID {$jobId}, Condition ID {$conditionId}");
    }

    /**
     * Handle the JobProcessed event.
     *
     * @param  JobProcessed  $event
     * @return void
     */
    public function handleJobProcessed(JobProcessed $event)
    {
        $jobId = $event->job->getJobId();

        Log::info("Job processed: Job ID {$jobId}");

        // Optionally, you can update the JobTracker status if needed
        JobTracker::where('job_id', $jobId)->update(['status' => 'processed']);
    }

    /**
     * Handle the JobFailed event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    public function handleJobFailed(JobFailed $event)
    {
        $jobId = $event->job->getJobId();

        Log::error("Job failed: Job ID {$jobId}, Exception: {$event->exception->getMessage()}");

        // Update JobTracker to reflect the failure status
        JobTracker::where('job_id', $jobId)->update(['status' => 'failed']);
    }
}
