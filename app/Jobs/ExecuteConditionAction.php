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
}
