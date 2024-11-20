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
                    $this->executeActionWithDelay($device, $action['delay'] ?? '00:00');
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

    private function executeActionWithDelay($device, $delay)
    {
        list($hours, $minutes) = explode(':', $delay);
        $delayInSeconds = ((int)$hours * 3600) + ((int)$minutes * 60);

        if ($delayInSeconds > 0) {
            Log::info("Delaying action for component {$device['component_id']} by {$delayInSeconds} seconds");
            sleep($delayInSeconds);
        }

        $this->executeAction($device);
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
        if (!$this->repetitionDays || !is_array($this->repetitionDays)) {
            Log::info("No valid repetition specified, job will not be rescheduled.");
            return;
        }

        $currentTime = Carbon::now();
        $today = strtolower($currentTime->format('l'));
        $repetitionDaysLower = array_map('strtolower', $this->repetitionDays);

        $nextExecutionDay = null;

        foreach ($repetitionDaysLower as $day) {
            if ($day === $today && $currentTime->isBefore($currentTime->copy()->endOfDay())) {
                $nextExecutionDay = $currentTime->addWeek();
                break;
            } elseif ($day > $today) {
                $nextExecutionDay = $currentTime->copy()->next($day);
                break;
            }
        }

        if (!$nextExecutionDay) {
            $nextExecutionDay = $currentTime->copy()->next($repetitionDaysLower[0]);
        }

        Log::info("Scheduling next execution for condition {$this->conditionId}, case {$this->caseId} on {$nextExecutionDay}");
        ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays)
            ->delay($nextExecutionDay);
    }
}
