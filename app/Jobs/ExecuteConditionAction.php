<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use App\Models\Action;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\MqttService ;

class ExecuteConditionAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $caseId;
    public $repetitionDays;
    public $isDelayed; // Flag to indicate if this is already a delayed job
    protected $mqttService;

    public function __construct($conditionId, $caseId, $repetitionDays = null, $isDelayed = false)
    {
        $this->conditionId = $conditionId;
        $this->caseId = $caseId;
        $this->repetitionDays = $repetitionDays;
        $this->isDelayed = $isDelayed; // Initialize the flag

        Log::info("Job created for condition {$conditionId}, case {$caseId}, isDelayed: " . ($isDelayed ? 'true' : 'false'));
    }

    public function handle(MqttService $mqttService)
    {
        $this->mqttService = $mqttService; // Injected automatically by Laravel

        Log::info("Job handling started for condition {$this->conditionId}, case {$this->caseId}, isDelayed: " . ($this->isDelayed ? 'true' : 'false'));

        // Retrieve condition and locate specific case by caseId
        $condition = Condition::find($this->conditionId);
        if (!$condition) {
            Log::error("Condition {$this->conditionId} not found.");
            return;
        }

        $cases = json_decode($condition->cases, true);
        $case = collect($cases)->firstWhere('case_id', $this->caseId);

        // Verify case existence and status
        if (!$case || !$case['is_active']) {
            Log::info("Case {$this->caseId} is inactive. Rescheduling for next repetition.");

            // Schedule the next execution if repetition days are defined
            if (!empty($this->repetitionDays)) {
                $this->scheduleNext();
            }

            return; // Skip execution for inactive case
        }

        // Evaluate `if` conditions
        $ifConditions = $case['if']['conditions'] ?? [];
        $ifLogic = $case['if']['logic'] ?? 'OR';

        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            Log::info("All 'if' conditions met for case {$this->caseId} in condition {$this->conditionId}");

            // Apply delay only if this is not already a delayed job
            $delay = $case['then']['delay'] ?? '00:00';
            if (!$this->isDelayed) {
                $this->dispatchDelayedJob($delay);
                return; // Exit as the delayed job will handle the execution
            }

            // Execute all actions in the `then` block
            foreach ($case['then']['actions'] as $action) {
                foreach ($action['devices'] as $device) {
                    $this->executeAction($device);
                }
            }
        } else {
            Log::info("One or more 'if' conditions failed for case {$this->caseId} in condition {$this->conditionId}");
        }

        // Schedule next execution if `repetitionDays` is specified
        $this->scheduleNext();
        Log::info("Job handling completed for condition {$this->conditionId}, case {$this->caseId}");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];
    
        foreach ($conditions as $condition) {
            // Only time condition without devices, automatically true
            if (is_null($condition['devices']) && !is_null($condition['time'])) {
                $results[] = true;
                continue;
            }
    
            // Both time and devices specified
            if (!is_null($condition['devices']) && !is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);

                    // Check if the component exists
                    if (!$component) {
                        $deviceResults[] = false;
                        continue; // Skip further checks for this device
                    }

                    // Retrieve the last message for the topic using the controller
                    $lastMessageJson = $this->getLastMessageFromController($component->device_id, $component->id);

                    // Compare jsonMap with last message content
                    $jsonMapMatch = isset($deviceCondition['jsonMap']) && $lastMessageJson === $deviceCondition['jsonMap'];

                    // Combine jsonMap checks
                    $deviceResults[] = $jsonMapMatch;
                }

                Log::info("deviceResults: " . json_encode($deviceResults));

                // Apply condition logic
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
                continue;
            }
    
            // Only devices specified
            if (!is_null($condition['devices']) && is_null($condition['time'])) {
                $deviceResults = [];
                foreach ($condition['devices'] as $deviceCondition) {
                    $component = Component::find($deviceCondition['component_id']);
    
                    // Check if the component exists
                    if (!$component) {
                        $deviceResults[] = false;
                        continue; // Skip further checks for this device
                    }
    
                    // Retrieve the last message for the topic using the controller
                    $lastMessageJson = $this->getLastMessageFromController($component->device_id, $component->id);
    
                    // Compare jsonMap with last message content
                    $jsonMapMatch = isset($deviceCondition['jsonMap']) && $lastMessageJson === $deviceCondition['jsonMap'];
    
                    // Combine jsonMap checks
                    $deviceResults[] = $jsonMapMatch;
                    Log::info("deviceResults : {$deviceResults} , deviceResults : {$jsonMapMatch}");
                }
    
                // Apply condition logic
                $results[] = $logic === 'AND' ? !in_array(false, $deviceResults) : in_array(true, $deviceResults);
            }
        }
    
        // Final evaluation of all conditions
        return $logic === 'AND' ? !in_array(false, $results, true) : in_array(true, $results, true);
    }    
    
    private function getLastMessageFromController($deviceId, $componentId)
    {
        $controller = app(\App\Http\Controllers\Api\MqttController::class);

        // Create a mock request for the controller method
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'device_id' => $deviceId,
            'component_id' => $componentId,
        ]);

        // Call the controller's method
        $response = $controller->subscribeToTopic($request);

        // Parse the response
        $responseData = $response->getData(true);

        // Return the last_message if it exists and is valid JSON
        if (isset($responseData['last_message'])) {
            Log::info("message of the mqtt : {$responseData['last_message']}");
            return $responseData['last_message'];
        }

        return null; // Return null if no message is found
    }


    private function dispatchDelayedJob($delay)
    {
        // Parse delay
        list($hours, $minutes) = explode(':', $delay);
        $delayInSeconds = ((int)$hours * 3600) + ((int)$minutes * 60);

        if ($delayInSeconds === 0) {
            Log::info("No delay required (delay: {$delay}). Proceeding immediately.");
            ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays, true);
            return;
        }

        Log::info("Dispatching delayed job for condition {$this->conditionId}, case {$this->caseId} by {$delayInSeconds} seconds.");
        ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays, true)
            ->delay(now()->addSeconds($delayInSeconds));
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        Log::info("Executed action for component", [
            'component' => $component,
        ]);
    
        if ($component) {
            // $component->update(['type' => "updated_type"]);
            $action = Action::find($device['action'])->first();
            Log::info("action_type", [
                'action' => $action,
            ]);
    
            if ($action && $action->action_type == "analog") {
                Log::info("Executed action for component", [
                    'action' => $action,
                    'json_data' => $action->json_data,
                ]);
    
                // Decode the json_data if it's a JSON string
                $actionContent = json_decode($action->json_data, true);  // Decode JSON string into an array
                // Now $actionContent is an array like ['status' => '0']
            } else {
                // If no action or the action type is not 'analog', manually create the content
                $actionContent = ['status' => $action->status];  // Make sure 'status' is valid
            }
    
            // Now $actionContent is an array with the desired format
            // Ensure the JSON is properly formatted without the 'action' key
    
            $result = $this->mqttService->publishAction($component->device_id, $component->order, $actionContent, true);
    
            Log::info("Executed action for component", [
                'component_id' => $device['component_id'],
                'action' => $device['action']
            ]);
        } else {
            Log::error("Component with ID {$device['component_id']} not found for action execution");
        }
    }    

    private function scheduleNext()
    {
        if (!$this->repetitionDays || !is_array($this->repetitionDays)) {
            Log::info("No valid repetition specified, job will not be rescheduled.");
            return;
        }

        $currentTime = Carbon::now();
        $today = $currentTime->format('l'); // Get today's name with the first letter capitalized

        // Normalize repetition days to strings and validate
        $repetitionDaysNormalized = array_filter(array_map(function ($day) {
            if (is_string($day)) {
                return ucfirst(strtolower(trim($day))); // Normalize to "Monday" format
            }
            Log::error("Invalid repetition day format: " . json_encode($day));
            return null;
        }, $this->repetitionDays));

        if (empty($repetitionDaysNormalized)) {
            Log::error("No valid repetition days found after filtering.");
            return;
        }

        // Determine the next execution day
        $nextExecutionDay = null;
        foreach ($repetitionDaysNormalized as $day) {
            if ($day === $today) {
                // If it's today, schedule for the same day next week
                $nextExecutionDay = $currentTime->copy()->addWeek();
                break;
            } elseif ($currentTime->lt(Carbon::parse($day))) {
                // If the day is later in the week
                $nextExecutionDay = $currentTime->copy()->next($day);
                break;
            }
        }

        if (!$nextExecutionDay) {
            // If no valid day is found, schedule for the first day in the repetition list
            $nextExecutionDay = $currentTime->copy()->next($repetitionDaysNormalized[0]);
        }

        Log::info("Scheduling next execution for condition {$this->conditionId}, case {$this->caseId} on {$nextExecutionDay}");

        ExecuteConditionAction::dispatch($this->conditionId, $this->caseId, $this->repetitionDays)
            ->delay($nextExecutionDay);
    }
}
