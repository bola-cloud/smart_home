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
    
        // Safely access the 'if' conditions with a fallback
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        Log::info("logic condition {$ifConditions} , {$ifLogic}");

    }      

    private function evaluateIfConditions($conditions, $logic)
    {    
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
