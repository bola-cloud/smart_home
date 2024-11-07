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
        $condition = Condition::find($this->conditionId);
        if ($condition) {
            $ifLogic = $condition->cases['if']['logic'];
            $ifConditions = $condition->cases['if']['conditions'];

            // Check if conditions are met before executing actions
            if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
                foreach ($this->action['devices'] as $device) {
                    $component = Component::find($device['component_id']);
                    if ($component) {
                        $component->update(['type' => $device['action']]);
                    }
                }
            }
        }

        $this->scheduleNext(); // Re-schedule if `repetition` is specified
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }

        return $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
    }

    private function evaluateSingleCondition($condition)
    {
        // Check device status
        foreach ($condition['devices'] as $device) {
            $component = Component::find($device['component_id']);
            if (!$component || $component->status != $device['status']) {
                return false; // Condition failed
            }
        }
    
        // Check time condition if set
        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            // Example logic for sunrise/sunset; replace with actual logic
            if ($condition['type'] === 'sunrise') {
                // Check if the current time is around sunrise time
                // Add your logic here
            } elseif ($condition['type'] === 'sunset') {
                // Check if the current time is around sunset time
                // Add your logic here
            } else {
                // Only specific time condition
                if (!$conditionTime->equalTo(Carbon::now())) {
                    return false; // Time does not match
                }
            }
        }
    
        return true; // Condition is met
    }
    

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'] ?? null;
        if (!$repetition) return;

        $nextExecution = match ($repetition) {
            'every_day' => Carbon::now()->addDay(),
            'every_week' => Carbon::now()->addWeek(),
            'every_month' => Carbon::now()->addMonth(),
            default => null,
        };

        if ($nextExecution) {
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        }
    }
}
