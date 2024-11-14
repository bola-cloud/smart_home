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
    public $caseId;
    public $repetitionDays;

    public function __construct($conditionId, $caseId, $repetitionDays = null)
    {
        $this->conditionId = $conditionId;
        $this->caseId = $caseId;
        $this->repetitionDays = $repetitionDays;

        Log::info("Job created for condition {$conditionId}, case {$caseId}");
    }

    public function handle()
    {
        Log::info("Job handling started for condition {$this->conditionId}, case {$this->caseId}");

        $condition = Condition::find($this->conditionId);
        if (!$condition) {
            Log::error("Condition {$this->conditionId} not found.");
            return;
        }

        $cases = json_decode($condition->cases, true);
        $case = collect($cases)->firstWhere('case_id', $this->caseId);

        if (!$case || !$case['is_active']) {
            Log::info("Case {$this->caseId} is inactive or not found; skipping execution.");
            return;
        }

        $ifConditions = $case['if']['conditions'] ?? [];
        $ifLogic = $case['if']['logic'] ?? 'OR';

        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for case {$this->caseId} in condition {$this->conditionId}");

            foreach ($case['then']['actions'] as $action) {
                foreach ($action['devices'] as $device) {
                    $this->executeAction($device);
                }
            }
        } else {
            Log::info("One or more 'if' conditions failed for case {$this->caseId} in condition {$this->conditionId}");
        }

        $this->scheduleNext();
        Log::info("Job handling completed for condition {$this->conditionId}, case {$this->caseId}");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        // Same logic as before for evaluating conditions
        $results = [];

        foreach ($conditions as $condition) {
            if (is_null($condition['devices']) && !is_null($condition['time'])) {
                $results[] = true;
                continue;
            }

            if (!is_null($condition['devices']) && !is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
                    $statusMatch = $component && isset($deviceCondition['status']) && $component->status == $deviceCondition['status'];
                    $deviceResults[] = $statusMatch;
                }
                $timeConditionMet = Carbon::now()->greaterThanOrEqualTo(Carbon::parse($condition['time']));
                $deviceResults[] = $timeConditionMet;
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
                continue;
            }

            if (!is_null($condition['devices']) && is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
                    $statusMatch = $component && isset($deviceCondition['status']) && $component->status == $deviceCondition['status'];
                    $deviceResults[] = $statusMatch;
                }
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
            }
        }

        return $logic === 'AND' ? !in_array(false, $results, true) : in_array(true, $results, true);
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => "updated_type"]);
            Log::info("Executed action for component", [
                'component_id' => $device['component_id'],
                'action' => $device['action']
            ]);
        } else {
            Log::error("Component with ID {$device['component_id']} not found for action execution");
        }
    }

    private function scheduleNext()
    {
        if (!$this->repetitionDays) {
            Log::info("No repetition specified, job will not be rescheduled.");
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
            Log::info("Scheduling next execution for case {$this->caseId}", [
                'next_execution' => $nextExecutionDay,
            ]);
            ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays)
                ->delay($nextExecutionDay);
        }
    }
}
