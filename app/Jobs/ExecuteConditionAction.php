<?php

namespace App\Jobs;

use App\Models\Condition;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteDeviceAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $action;

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;
    }

    public function handle()
    {
        // Execute the action logic here
        $condition = Condition::find($this->conditionId);
        if ($condition) {
            foreach ($this->action['devices'] as $device) {
                // Implement action for each device, e.g., turn on/off
                // Example: $device['device_id'], $device['action']
            }
        }

        // Schedule the next execution based on the repetition
        $this->scheduleNext();
    }

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'];
        $actionTime = Carbon::parse($this->action['time']);
        $nextExecution = null;

        switch ($repetition) {
            case 'every_day':
                $nextExecution = $actionTime->addDay();
                break;
            case 'every_week':
                $nextExecution = $actionTime->addWeek();
                break;
            case 'every_month':
                $nextExecution = $actionTime->addMonth();
                break;
            case null:
                // If repetition is null, no re-scheduling
                return;
        }

        if ($nextExecution) {
            ExecuteDeviceAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        }
    }
}