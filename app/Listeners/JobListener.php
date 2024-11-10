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
        Log::info('Job queued: ', ['job' => $event->job->resolveName()]);
    }

    /**
     * Handle the JobProcessed event.
     *
     * @param  JobProcessed  $event
     * @return void
     */
    public function handleJobProcessed(JobProcessed $event)
    {
        Log::info('Job processed: ', ['job' => $event->job->resolveName()]);
    }

    /**
     * Handle the JobFailed event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    public function handleJobFailed(JobFailed $event)
    {
        Log::error('Job failed: ', ['job' => $event->job->resolveName(), 'exception' => $event->exception]);
    }
}
