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

        Log::info("Condition found and case is active", [
            'condition_id' => $this->conditionId,
            'if_conditions' => $condition->cases['if']['conditions'] ?? [],
            'if_logic' => $condition->cases['if']['logic'] ?? 'OR',
        ]);

        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
        
        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");

            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $componentState = $this->checkComponentState($device['component_id']);
                    Log::info("Checked component state", [
                        'component_id' => $device['component_id'],
                        'expected_status' => $device['status'],
                        'found_state' => $componentState,
                    ]);

                    if ($componentState === $device['status']) {
                        Log::info("Condition met, executing action", ['component_id' => $device['component_id']]);
                        $this->executeAction($device);
                    } else {
                        Log::info("Condition not met, skipping execution", ['component_id' => $device['component_id']]);
                    }
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
        Log::info("Evaluating conditions with forced return for testing.");
        return true;
    }

    private function checkComponentState($componentId)
    {
        $component = Component::find($componentId);
        if ($component) {
            return $component->status;  // Adjust this line to reflect the actual state field in your Component model
        } else {
            Log::error("Component with ID {$componentId} not found.");
            return null;
        }
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
            Log::info("Scheduling next execution", [
                'condition_id' => $this->conditionId,
                'next_execution' => $nextExecution,
            ]);
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        } else {
            Log::error("Invalid repetition interval specified", ['repetition' => $repetition]);
        }
    }
}
