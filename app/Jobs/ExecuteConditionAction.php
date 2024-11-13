<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteConditionAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $action;
    public $repetitionDays;

    public function __construct($conditionId, $action, $repetitionDays = null)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;
        $this->repetitionDays = $repetitionDays;

        Log::info("Job created for condition {$conditionId} with action", $action);
    }

    public function handle()
    {
        Log::info("Job handling started for condition {$this->conditionId}");

        $condition = Condition::find($this->conditionId);
        if (!$condition) {
            Log::error("Condition {$this->conditionId} not found.");
            return;
        }

        $caseId = $this->action['case_id'] ?? null;
        if (!$caseId) {
            Log::error("Missing case_id in action for condition {$this->conditionId}.");
            return;
        }

        if (!$condition->isCaseActive($caseId)) {
            Log::info("Job for case {$caseId} is inactive and will not execute.");
            return;
        }

        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';

        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");

            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $this->executeAction($device);
                }
            } else {
                Log::error("No devices provided in the 'then' actions for condition {$this->conditionId}");
            }
        } else {
            Log::info("One or more 'if' conditions failed for condition {$this->conditionId}");
        }

        $this->scheduleNext();
        Log::info("Job handling completed for condition {$this->conditionId}");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];
    
        foreach ($conditions as $condition) {
            // Check for time condition only (if devices are not specified)
            if (empty($condition['devices']) && !empty($condition['time'])) {
                $timeConditionMet = Carbon::now()->greaterThanOrEqualTo(Carbon::parse($condition['time']));
                $results[] = $timeConditionMet;
                Log::info("Time-only condition evaluated", ['condition_time' => $condition['time'], 'result' => $timeConditionMet]);
            }
    
            // Check for conditions that include devices
            if (!empty($condition['devices'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
                    $statusMatch = $component && $component->status == $deviceCondition['status'];
                    $deviceResults[] = $statusMatch;
                    Log::info("Device condition evaluated", [
                        'component_id' => $deviceCondition['component_id'],
                        'expected_status' => $deviceCondition['status'],
                        'actual_status' => $component->status ?? 'not found',
                        'result' => $statusMatch
                    ]);
                }
    
                // If there's also a time condition, combine the time condition with the device results
                if (!empty($condition['time'])) {
                    $timeConditionMet = Carbon::now()->greaterThanOrEqualTo(Carbon::parse($condition['time']));
                    $deviceResults[] = $timeConditionMet;
                    Log::info("Time and device condition evaluated", [
                        'condition_time' => $condition['time'],
                        'time_result' => $timeConditionMet
                    ]);
                }
    
                // Apply the logic to the device results for this specific condition
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
            }
        }
    
        // Final evaluation of all conditions according to the main logic (AND/OR)
        Log::info("Final condition evaluation", ['results' => $results]);
        return $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
    }    

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => "bola2"]);
            Log::info("Executed action", [
                'action' => $device['action'],
                'component_id' => $device['component_id'],
            ]);
        } else {
            Log::error("Failed to find component with ID {$device['component_id']} for action execution");
        }
    }

    private function scheduleNext()
    {
        if (!$this->repetitionDays) {
            Log::info("No repetition specified, job will not be rescheduled");
            return;
        }

        $today = Carbon::now()->format('l');
        $nextExecutionDay = null;

        foreach ($this->repetitionDays as $day) {
            if (strtolower($day) === strtolower($today)) {
                $nextExecutionDay = Carbon::now()->addWeek();
                break;
            }
        }

        if ($nextExecutionDay) {
            Log::info("Scheduling next execution", [
                'condition_id' => $this->conditionId,
                'next_execution' => $nextExecutionDay,
            ]);
            ExecuteConditionAction::dispatch($this->conditionId, $this->action, $this->repetitionDays)
                ->delay($nextExecutionDay);
        }
    }
}
