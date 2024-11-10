<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
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
        // Log the job class name instead of using resolveName()
        Log::info('Job queued: ', ['job' => get_class($event->job)]);
    }

    /**
     * Handle the JobProcessed event.
     *
     * @param  JobProcessed  $event
     * @return void
     */
    public function handleJobProcessed(JobProcessed $event)
    {
        // Log the job class name
        Log::info('Job processed: ', ['job' => get_class($event->job)]);
    }

    /**
     * Handle the JobFailed event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    public function handleJobFailed(JobFailed $event)
    {
        // Log the job class name and the exception message
        Log::error('Job failed: ', [
            'job' => get_class($event->job),
            'exception' => $event->exception
        ]);
    }
}
