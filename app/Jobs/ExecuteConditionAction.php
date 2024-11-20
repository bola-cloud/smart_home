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

        // Retrieve condition and locate specific case by caseId
        $condition = Condition::find($this->conditionId);
        if (!$condition) {
            Log::error("Condition {$this->conditionId} not found.");
            return;
        }

        $cases = json_decode($condition->cases, true);
        $case = collect($cases)->firstWhere('case_id', $this->caseId);

        // Verify case existence and status
        if (!$case || !$case['is_active']) {
            Log::info("Case {$this->caseId} is inactive or not found; skipping execution.");
            return;
        }

        // Evaluate `if` conditions
        $ifConditions = $case['if']['conditions'] ?? [];
        $ifLogic = $case['if']['logic'] ?? 'OR';

        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for case {$this->caseId} in condition {$this->conditionId}");

            // Execute each action in the `then` block with specified delays
            foreach ($case['then']['actions'] as $action) {
                foreach ($action['devices'] as $device) {
                    $this->executeActionWithDelay($device, $action['delay'] ?? '00:00');
                }
            }
        } else {
            Log::info("One or more 'if' conditions failed for case {$this->caseId} in condition {$this->conditionId}");
        }

        // Schedule next execution if `repetitionDays` is specified
        $this->scheduleNext();
        Log::info("Job handling completed for condition {$this->conditionId}, case {$this->caseId}");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];

        foreach ($conditions as $condition) {
            // Only time condition without devices, automatically true
            if (is_null($condition['devices']) && !is_null($condition['time'])) {
                $results[] = true;
                continue;
            }

            // Both time and devices specified
            if (!is_null($condition['devices']) && !is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
                    $statusMatch = $component && isset($deviceCondition['status']) && $component->status == $deviceCondition['status'];
                    $deviceResults[] = $statusMatch;
                }
                // Time condition must also match
                $timeConditionMet = Carbon::now()->greaterThanOrEqualTo(Carbon::parse($condition['time']));
                $deviceResults[] = $timeConditionMet;

                // Apply condition logic
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
                continue;
            }

            // Only devices specified
            if (!is_null($condition['devices']) && is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
                    $statusMatch = $component && isset($deviceCondition['status']) && $component->status == $deviceCondition['status'];
                    $deviceResults[] = $statusMatch;
                }
                // Apply condition logic
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
            }
        }

        // Final evaluation of all conditions
        return $logic === 'AND' ? !in_array(false, $results, true) : in_array(true, $results, true);
    }

    private function executeActionWithDelay($device, $delay)
    {
        // Parse delay
        list($hours, $minutes) = explode(':', $delay);
        $delayInSeconds = ((int)$hours * 3600) + ((int)$minutes * 60);

        // Delay execution
        if ($delayInSeconds > 0) {
            Log::info("Delaying action for component {$device['component_id']} by {$delayInSeconds} seconds");
            sleep($delayInSeconds);
        }

        // Execute action
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

    private function as()
    {
        if (!$this->repetitionDays || !is_array($this->repetitionDays)) {
            Log::info("No valid repetition specified, job will not be rescheduled.");
            return;
        }
    
        $currentTime = Carbon::now();
        $today = strtolower($currentTime->format('l')); // Get current day name in lowercase
    
        // Normalize repetition days to strings and lowercase
        $repetitionDaysLower = array_map(function ($day) {
            if (is_string($day)) {
                return strtolower($day);
            }
            if (is_array($day)) {
                Log::error("Invalid repetition day format: array provided instead of string.");
            } else {
                Log::error("Invalid repetition day format: " . json_encode($day));
            }
            return null;
        }, $this->repetitionDays);
    
        // Filter out invalid days (null values)
        $repetitionDaysLower = array_filter($repetitionDaysLower);
    
        // Check if there are any valid days left
        if (empty($repetitionDaysLower)) {
            Log::error("No valid repetition days found after filtering.");
            return;
        }
    
        $nextExecutionDay = null;
    
        foreach ($repetitionDaysLower as $day) {
            if ($day === $today && $currentTime->isBefore($currentTime->copy()->endOfDay())) {
                // If today matches and there's time left, schedule for today next week
                $nextExecutionDay = $currentTime->addWeek();
                break;
            }
            if ($day > $today) {
                // Schedule for the next matching day
                $nextExecutionDay = $currentTime->copy()->next($day);
                break;
            }
        }
    
        if (!$nextExecutionDay) {
            // If no matching day found, schedule for the first repetition day next week
            $nextExecutionDay = $currentTime->copy()->next($repetitionDaysLower[0]);
        }
    
        Log::info("Scheduling next execution for condition {$this->conditionId}, case {$this->caseId} on {$nextExecutionDay}");
        ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays)
            ->delay($nextExecutionDay);
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
