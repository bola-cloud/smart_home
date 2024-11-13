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

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;

        Log::info("Job created for condition {$conditionId} with action: ", $action);
    }

    public function handle()
    {
        Log::info("Job handling started for condition {$this->conditionId}");

        $condition = Condition::find($this->conditionId);

        if (!$condition) {
            Log::error("Condition {$this->conditionId} not found.");
            return;
        }

        // Ensure `case_id` exists in `$this->action`
        $caseId = $this->action['case_id'] ?? null;
        if (!$caseId) {
            Log::error("Missing case_id in action for condition {$this->conditionId}.");
            return;
        }

        // Check if the case is active
        if (!$condition->isCaseActive($caseId)) {
            Log::info("Job for case {$caseId} is inactive and will not execute.");
            return;
        }

        Log::info("Condition found and case is active for condition {$this->conditionId}");

        // Safely access and log 'if' conditions
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        Log::info("Condition's 'if' conditions and logic retrieved", [
            'if_conditions' => json_encode($ifConditions),
            'if_logic' => $ifLogic,
        ]);

        // Force evaluateIfConditions to return true for testing
        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");

            // Execute each device action in 'then' if available
            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $this->executeAction($device); // Skipping device check for now
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
        Log::info("Forcing evaluateIfConditions to return true for testing purposes.");
        return true;
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => "bola2"]);
            Log::info("Executed action on component ID {$device['component_id']}");
        } else {
            Log::error("Failed to find component with ID {$device['component_id']} for action execution");
        }
    }

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'] ?? null;
        if (!$repetition) {
            Log::info("No repetition specified, job will not be rescheduled");
            return;
        }

        $nextExecution = match ($repetition) {
            'every_day' => Carbon::now()->addDay(),
            'every_week' => Carbon::now()->addWeek(),
            'every_month' => Carbon::now()->addMonth(),
            default => null,
        };

        if ($nextExecution) {
            Log::info("Scheduling next execution for condition {$this->conditionId} at {$nextExecution}");
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        } else {
            Log::error("Invalid repetition interval specified: {$repetition}");
        }
    }
}
