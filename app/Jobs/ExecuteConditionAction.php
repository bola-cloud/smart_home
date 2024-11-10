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
    }

    /**
     * Handle the job execution.
     *
     * @return void
     */
    public function handle()
    {
        // Track the job ID for monitoring or cancellation purposes
        $this->jobId = $this->job->getJobId(); // Get the unique job ID
        Log::info("Job {$this->jobId} started for condition {$this->conditionId}");

        // Fetch the condition and execute the action if the condition is met
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

        $this->scheduleNext(); // Re-schedule if repetition is specified
    }

    /**
     * Evaluate all conditions with global logic (AND/OR).
     *
     * @param  array  $conditions
     * @param  string  $logic
     * @return bool
     */
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

    /**
     * Evaluate a single condition.
     *
     * @param  array  $condition
     * @return bool
     */
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

    /**
     * Check the last known state of the component.
     *
     * @param  int  $componentId
     * @return mixed
     */
    private function checkComponentState($componentId)
    {
        // Use the MqttService to get the current state of the component
        $mqttService = new MqttService();
        return $mqttService->getLastState($componentId);
    }

    /**
     * Execute the action on the device if conditions are met.
     *
     * @param  array  $device
     * @return void
     */
    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            // Perform the action (e.g., turn on/off the component)
            $component->update(['type' => $device['action']]);
            Log::info("Executed action: {$device['action']} on component: {$device['component_id']}");
        }
    }

    /**
     * Schedule the next job if repetition is specified.
     *
     * @return void
     */
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

    /**
     * Stop the job by deleting it from the queue.
     *
     * @return void
     */
    public function deleteJob()
    {
        // Mark the job as deleted by calling the delete method.
        $this->delete();
        Log::info("Job {$this->jobId} has been cancelled.");
    }
}
