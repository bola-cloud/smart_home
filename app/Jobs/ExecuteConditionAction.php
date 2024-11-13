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
    
        // Access the 'if' conditions safely with detailed logging
        $ifLogic = $condition->cases['if']['logic'] ?? 'OR';
        $ifConditions = $condition->cases['if']['conditions'] ?? [];
        Log::info("Condition's 'if' conditions and logic retrieved", [
            'if_conditions' => $ifConditions,
            'if_logic' => $ifLogic,
        ]);
    
        // Force evaluateIfConditions to return true for testing purposes
        $evaluationResult = $this->evaluateIfConditions($ifConditions, $ifLogic);
        Log::info("Forced evaluation result:", ['evaluation_result' => $evaluationResult]);
    
        if ($evaluationResult) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");
            // Perform actions here, such as calling executeAction
            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $this->executeAction($device); // Commented device checking for testing
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
}
