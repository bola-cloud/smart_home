<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

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
        // Fetch the condition and execute the action
        $condition = Condition::find($this->conditionId);
        if ($condition) {
            foreach ($this->action['devices'] as $device) {
                $component = Component::find($device['device_id']);
                if ($component) {
                    // Execute the action based on the device's action type
                    $component->update(['type' => $device['action']]);
                }
            }
        }

        // Schedule the next execution based on repetition
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
                // If repetition is null, no re-scheduling is needed
                return;
        }

        if ($nextExecution) {
            $delay = $nextExecution->diffInSeconds(Carbon::now());
            ExecuteDeviceAction::dispatch($this->conditionId, $this->action)
                ->delay(now()->addSeconds($delay));
        }
    }
}
