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
        foreach ($condition['devices'] as $device) {
            $component = Component::find($device['component_id']);
            if (!$component || $component->status != $device['status']) {
                return false;
            }
        }

        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            if (!$conditionTime->equalTo(Carbon::now())) {
                return false;
            }
        }

        return true;
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
