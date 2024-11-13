<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use App\Services\MqttService;
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
        Log::info("Bola");

        // Safely access the 'if' conditions with a fallback
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        Log::info("Condition found and case is active for condition {$ifConditions} ,{$ifLogic}");
        // Evaluate the "if" conditions
        Log::info("Evaluating 'if' conditions for condition {$this->conditionId}");
        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");
    
            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $componentState = $this->checkComponentState($device['component_id']);
                    Log::info("Checked component state for component ID {$device['component_id']} with expected status {$device['status']}, found: {$componentState}");
    
                    if ($componentState === $device['status']) {
                        Log::info("Condition met for action on component {$device['component_id']}, executing action");
                        $this->executeAction($device);
                    } else {
                        Log::info("Condition not met for action on component {$device['component_id']}, skipping execution");
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
        // Log::info("Evaluating conditions with logic {$logic}");
        // Log::info("Conditions data structure:", $conditions); // Log the structure of the conditions data
    
        // $results = [];
        
        // foreach ($conditions as $condition) {
        //     Log::info("start foreach");
        //     // If there are no devices and a time is specified, consider the condition as `true`
        //     if (empty($condition['devices']) && !empty($condition['time'])) {
        //         Log::info("Only time condition specified, defaulting to true for this condition.");
        //         $results[] = true;
        //     } else {
        //         // Otherwise, evaluate the condition normally
        //         $result = $this->evaluateSingleCondition($condition);
        //         Log::info("Single condition evaluation result: " . ($result ? 'true' : 'false'));
        //         $results[] = $result;
        //     }
        // }
    
        // Log::info("Condition evaluation results array: ", $results);
    
        // // Apply the AND/OR logic to the array of results
        // $finalResult = $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
        // Log::info("Final evaluation result for conditions with logic {$logic}: " . ($finalResult ? 'true' : 'false'));
    
        Log::info("Forcing evaluateIfConditions to return true for testing purposes.");
        return true;
    }
    
    private function evaluateSingleCondition($condition)
    {
        // Force return true for testing, with logging for validation
        Log::info("Evaluating single condition - forced to true for testing purposes.");
        return true;
    }    

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        // if ($component) {
        //     $component->update(['type' => $device['action']]);
        //     Log::info("Executed action: {$device['action']} on component: {$device['component_id']}");
        // } else {
        //     Log::error("Failed to find component with ID {$device['component_id']} for action execution");
        // }
        if ($component) {
            $component->update(['type' => "bola2"]);
            Log::info("Executed action: {$device['action']} on component: {$device['component_id']}");
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
