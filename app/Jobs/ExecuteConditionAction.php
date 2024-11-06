<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\Condition;
use App\Models\Component;

class ExecuteConditionAction implements ShouldQueue
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
        // Fetch the condition
        $condition = Condition::find($this->conditionId);
        if ($condition) {
            // Check `if` conditions with global logic before executing `then` actions
            $ifLogic = $this->action['if']['logic'];
            $ifConditions = $this->action['if']['conditions'];

            if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
                $thenActions = $this->action['then']['actions'];
                foreach ($thenActions as $action) {
                    foreach ($action['devices'] as $device) {
                        $component = Component::find($device['device_id']);
                        if ($component) {
                            $component->update(['type' => "bola"]);
                        }
                    }
                }
            }
        }

        // Schedule the next execution based on repetition
        $this->scheduleNext();
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];

        foreach ($conditions as $condition) {
            // Evaluate each condition (e.g., device status and time match)
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }

        if ($logic === 'AND') {
            return !in_array(false, $results);
        } elseif ($logic === 'OR') {
            return in_array(true, $results);
        }

        return false; // Default case if logic isn't met
    }

    private function evaluateSingleCondition($condition)
    {
        // Check device status
        foreach ($condition['devices'] as $device) {
            $component = Component::find($device['device_id']);
            if (!$component || $component->status != $device['status']) {
                return false;
            }
        }

        // Check time condition if set
        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            if (!$conditionTime->equalTo(Carbon::now())) {
                return false;
            }
        }

        return true; // Condition is met
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
                return; // No repetition, only execute once
        }

        if ($nextExecution) {
            $delay = $nextExecution->diffInSeconds(Carbon::now());
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay(now()->addSeconds($delay));
        }
    }
}
