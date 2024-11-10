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

class ExecuteConditionAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $action;

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;
    }

    public function handle()
    {
        $condition = Condition::find($this->conditionId);
        if ($condition && $condition->is_active) {
            $ifLogic = $condition->cases['if']['logic'];
            $ifConditions = $condition->cases['if']['conditions'];

            // Check if conditions are met before executing actions
            if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
                // Execute actions only if conditions are satisfied
                foreach ($this->action['devices'] as $device) {
                    $componentState = $this->checkComponentState($device['component_id']);

                    // Compare the state and execute the action if the state matches
                    if ($componentState === $device['status']) {
                        $this->executeAction($device);
                    }
                }
            }
        }

        $this->scheduleNext(); // Re-schedule if `repetition` is specified
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }

        // Apply global logic: AND or OR
        return $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
    }

    private function evaluateSingleCondition($condition)
    {
        // Initialize MqttService to get the last state of the component
        $mqttService = new MqttService();
    
        // Check device state
        foreach ($condition['devices'] as $device) {
            // Get the component state from MQTT service
            $componentState = $mqttService->getLastState($device['component_id']);
            
            // If the component state is not found or does not match the expected status, return false
            if ($componentState === null || $componentState != $device['status']) {
                return false; // Condition not met
            }
        }
    
        // Check time condition (if time condition is specified)
        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            if (!$conditionTime->equalTo(Carbon::now())) {
                return false; // Time does not match
            }
        }
    
        return true; // Condition is met
    }    

    private function checkComponentState($componentId)
    {
        // Use the MqttService to get the current state of the component
        $mqttService = new MqttService();
        return $mqttService->getLastState($componentId);
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            // Perform the action (e.g., turn on/off the component)
            $component->update(['type' => $device['action']]);
            echo "Executed action: {$device['action']} on component: {$device['component_id']}\n";
        }
    }

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'] ?? null;
        if (!$repetition) return;

        // Schedule the next execution based on repetition
        $nextExecution = match ($repetition) {
            'every_day' => Carbon::now()->addDay(),
            'every_week' => Carbon::now()->addWeek(),
            'every_month' => Carbon::now()->addMonth(),
            default => null,
        };

        if ($nextExecution) {
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        }
    }
}
