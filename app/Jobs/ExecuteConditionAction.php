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
    protected $jobId;

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

        // Check if the case is active
        if ($condition && !$condition->isCaseActive($this->action['case_id'])) {
            Log::info("Job for case {$this->action['case_id']} is inactive and will not execute.");
            return; // Exit without executing the job
        }

        Log::info("Condition found and case is active for condition {$this->conditionId}");

        $ifLogic = $condition->cases['if']['logic'];
        $ifConditions = $condition->cases['if']['conditions'];

        // Evaluate the "if" conditions
        Log::info("Evaluating 'if' conditions for condition {$this->conditionId}");
        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for condition {$this->conditionId}");

            // Check and execute each device action in the "then" part
            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $componentState = $this->checkComponentState($device['component_id']);
                    Log::info("Checked component state for component ID {$device['component_id']} with expected status {$device['status']}, found: {$componentState}");

                    // Execute the action if component state matches
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

        $this->scheduleNext(); // Reschedule if `repetition` is specified
        Log::info("Job handling completed for condition {$this->conditionId}");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        Log::info("Evaluating conditions with logic {$logic}");
        $results = [];
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }
        $finalResult = $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
        Log::info("Evaluation result for conditions with logic {$logic}: " . ($finalResult ? 'true' : 'false'));
        return $finalResult;
    }

    private function evaluateSingleCondition($condition)
    {
        Log::info("Evaluating single condition: ", $condition);
        $mqttService = new MqttService();
    
        foreach ($condition['devices'] as $device) {
            $componentState = $mqttService->getLastState($device['component_id']);
            Log::info("Device state for component ID {$device['component_id']} is {$componentState}, expected: {$device['status']}");
            if ($componentState === null || $componentState != $device['status']) {
                return false;
            }
        }
    
        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            $currentTime = Carbon::now()->format('Y-m-d H:i');
            Log::info("Checking time condition: expected {$conditionTime}, current time {$currentTime}");
            if ($conditionTime->format('Y-m-d H:i') !== $currentTime) {
                return false;
            }
        }
    
        return true;
    }
    
    private function checkComponentState($componentId)
    {
        $mqttService = new MqttService();
        $componentState = $mqttService->getLastState($componentId);
        Log::info("Fetched last state for component ID {$componentId}: {$componentState}");
        return $componentState;
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => $device['action']]);
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

        // Calculate next execution time based on repetition interval
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
