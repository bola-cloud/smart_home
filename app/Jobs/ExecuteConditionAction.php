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
    
        $caseId = $this->action['case_id'] ?? null;
        if (!$caseId) {
            Log::error("Missing case_id in action for condition {$this->conditionId}.");
            return;
        }
    
        if (!$condition->isCaseActive($caseId)) {
            Log::info("Job for case {$caseId} is inactive and will not execute.");
            return;
        }
    
        Log::info("Condition found and case is active for condition {$this->conditionId}");
    
        // Check time-based "if" conditions only
        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
    
        Log::info("Evaluating 'if' conditions for condition {$this->conditionId}");
        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");
            $this->executeActions($this->action['devices'] ?? []);
        } else {
            Log::info("One or more 'if' conditions failed for condition {$this->conditionId}");
        }
    
        $this->scheduleNext();
        Log::info("Job handling completed for condition {$this->conditionId}");
    }
          

    private function evaluateIfConditions($conditions, $logic = 'OR')
    {
        Log::info("Evaluating conditions with logic {$logic}");
        $results = [];
    
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }
    
        $finalResult = ($logic === 'AND') ? !in_array(false, $results) : in_array(true, $results);
        Log::info("Evaluation result for conditions with logic {$logic}: " . ($finalResult ? 'true' : 'false'));
    
        return $finalResult;
    }  

    private function evaluateSingleCondition($condition)
    {
        // Log::info("Evaluating single condition after time delay:", ['condition' => $condition]);
        // $mqttService = new MqttService();
    
        // // Check device-specific conditions if present
        // if (!empty($condition['devices'])) {
        //     foreach ($condition['devices'] as $device) {
        //         $componentState = $mqttService->getLastState($device['component_id']);
        //         Log::info("Device state for component ID {$device['component_id']} is {$componentState}, expected: {$device['status']}");
        //         if ($componentState === null || $componentState != $device['status']) {
        //             return false;
        //         }
        //     }
        // }
    
        return true;
    }      

    private function checkComponentState($componentId)
    {
        // $mqttService = new MqttService();
        // $componentState = $mqttService->getLastState($componentId);
        // Log::info("Fetched last state for component ID {$componentId}: {$componentState}");
        // return $componentState;
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
