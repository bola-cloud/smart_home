<?php

namespace App\Http\Controllers\Api\Conditions;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Support\Facades\Queue;
use App\Models\JobTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Jobs\ExecuteConditionAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConditionsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'cases' => 'required|array',
            'cases.*.name' => 'required|string|max:256',
            'cases.*.is_active' => 'nullable|boolean',
            'cases.*.repetition' => 'nullable|array',
            'cases.*.repetition.*' => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.if.conditions.*.devices.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap for 'if'
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i|after_or_equal:now', // Time must be in the future or null
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.action' => 'required|array',
            'cases.*.then.actions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap for 'then'
            'cases.*.then.delay' => 'nullable|date_format:H:i',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $user = Auth::user();
        // Check if a condition already exists for the user and project
        $existingCondition = Condition::where('user_id', $user->id)
            ->where('project_id', $request->project_id)
            ->first();
    
        if ($existingCondition) {
            return response()->json([
                'status' => false,
                'message' => 'A condition for this user and project already exists.',
            ], 409);
        }
    
        $cases = $request->cases;
    
        // Process each case to handle jsonMap and assign unique IDs
        $cases = array_map(function ($case) {
            $case['case_id'] = uniqid(); // Assign unique case_id
    
            // Process jsonMap in the 'if' block
            foreach ($case['if']['conditions'] as &$conditionBlock) {
                if (isset($conditionBlock['devices']) && is_array($conditionBlock['devices'])) {
                    foreach ($conditionBlock['devices'] as &$device) {
                        // Set jsonMap to null if it is empty or missing
                        $device['jsonMap'] = isset($device['jsonMap']) && !empty($device['jsonMap']) ? $device['jsonMap'] : null;
                    }
                }
            }
    
            // Process jsonMap in the 'then' block
            foreach ($case['then']['actions'] as &$action) {
                if (isset($action['devices']) && is_array($action['devices'])) {
                    foreach ($action['devices'] as &$device) {
                        // Set jsonMap to null if it is empty or missing
                        $device['jsonMap'] = isset($device['jsonMap']) && !empty($device['jsonMap']) ? $device['jsonMap'] : null;
                    }
                }
            }
    
            return $case;
        }, $cases);
    
        // Store the condition in the database
        $condition = Condition::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'cases' => json_encode($cases),
        ]);
    
        // Schedule actions for each case
        foreach ($cases as $case) {
            $ifConditions = $case['if']['conditions'];
            foreach ($case['then']['actions'] as $action) {
                $this->scheduleAction($action, $condition->id, $case['case_id'], $ifConditions, $case['repetition'] ?? null);
                Log::info("scheduleAction with ID case {$case['case_id']} in condition {$condition->id}");
            }
        }
    
        // Return the created condition in the desired format
        return response()->json([
            'status' => true,
            'message' => 'Condition created successfully with schedules',
            'data' => [
                [
                    'id' => $condition->id,
                    'user_id' => $condition->user_id,
                    'project_id' => $condition->project_id,
                    'cases' => json_decode($condition->cases), // Decode cases to include in the response
                ],
            ],
        ], 200);
    }    

    private function scheduleAction($action, $conditionId, $caseId, $ifConditions, $repetitionDays = null)
    {
        $jobId = Str::uuid()->toString();
        $action['case_id'] = $caseId;
    
        // Parse delay in "HH:mm" format
        $delayInSeconds = 0;
        if (!empty($action['delay'])) {
            list($hours, $minutes) = explode(':', $action['delay']);
            $delayInSeconds = ((int)$hours * 3600) + ((int)$minutes * 60);
        }
    
        $scheduledTime = null;
    
        // Check for `time` in `ifConditions`
        foreach ($ifConditions as $condition) {
            if (!empty($condition['time'])) {
                $scheduledTime = Carbon::parse($condition['time']);
                break;
            }
        }
    
        if ($scheduledTime) {
            $currentTime = Carbon::now();
            $baseDelay = $currentTime->diffInSeconds($scheduledTime, false);
    
            if ($baseDelay < 0) {
                $baseDelay += 86400; // Handle negative delay for the next day
            }
    
            // Pass `repetitionDays` when dispatching
            $job = ExecuteConditionAction::dispatch($conditionId, $caseId, $repetitionDays)
                ->delay(now()->addSeconds($baseDelay));
        } else {
            // Pass `repetitionDays` even when no scheduled time is set
            $job = ExecuteConditionAction::dispatch($conditionId, $caseId, $repetitionDays);
        }
    
        JobTracker::create([
            'job_id' => $jobId,
            'condition_id' => $conditionId,
            'case_id' => $caseId,
        ]);
    
        Log::info("Scheduled job with ID {$jobId} for case {$caseId} in condition {$conditionId}");
    }

    public function editCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:conditions,id',
            'case_id' => 'required|string',
            'cases.*.name' => 'required|string|max:256',
            'cases.*.is_active' => 'nullable|boolean',
            'cases.*.repetition' => 'nullable|array',
            'cases.*.repetition.*' => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.if.conditions.*.devices.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap for 'if'
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap for 'then'
            'cases.*.then.delay' => 'nullable|date_format:H:i',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $condition = Condition::find($request->id);
        $updatedCase = $request->cases[0];
    
        // Decode the existing cases
        $existingCases = json_decode($condition->cases, true);
    
        // Find the index of the case to update
        $caseIndex = null;
        foreach ($existingCases as $index => $case) {
            if ($case['case_id'] === $request->case_id) {
                $caseIndex = $index;
                break;
            }
        }
    
        if ($caseIndex === null) {
            return response()->json([
                'status' => false,
                'message' => 'Case not found in the condition.',
            ], 404);
        }
    
        // Cancel the existing job for the case
        $this->cancelCaseJobs($condition->id, $request->case_id);
    
        // Process jsonMap in the 'if' block
        foreach ($updatedCase['if']['conditions'] as &$conditionBlock) {
            if (isset($conditionBlock['devices']) && is_array($conditionBlock['devices'])) {
                foreach ($conditionBlock['devices'] as &$device) {
                    // Ensure jsonMap is null if it is empty or missing
                    $device['jsonMap'] = isset($device['jsonMap']) && !empty($device['jsonMap']) ? $device['jsonMap'] : null;
                }
            }
        }
    
        // Process jsonMap in the 'then' block
        foreach ($updatedCase['then']['actions'] as &$action) {
            if (isset($action['devices']) && is_array($action['devices'])) {
                foreach ($action['devices'] as &$device) {
                    // Ensure jsonMap is null if it is empty or missing
                    $device['jsonMap'] = isset($device['jsonMap']) && !empty($device['jsonMap']) ? $device['jsonMap'] : null;
                }
            }
        }
    
        // Update the case in the array
        $existingCases[$caseIndex] = array_merge($existingCases[$caseIndex], $updatedCase);
    
        // Save the updated cases back to the condition
        $condition->cases = json_encode($existingCases);
        $condition->save();
    
        // Schedule the updated case
        foreach ($updatedCase['then']['actions'] as $action) {
            $this->scheduleAction($action, $condition->id, $request->case_id, $updatedCase['if']['conditions'], $updatedCase['repetition'] ?? null);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Case updated successfully and rescheduled.',
            'data' => [
                [
                    'id' => $condition->id,
                    'user_id' => $condition->user_id,
                    'project_id' => $condition->project_id,
                    'cases' => json_decode($condition->cases), // Decode cases to include in the response
                ],
            ],
        ], 200);
    }        

    public function addCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:conditions,id',
            'cases.*.name' => 'required|string|max:256',
            'cases.*.is_active' => 'nullable|boolean',
            'cases.*.repetition' => 'nullable|array',
            'cases.*.repetition.*' => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.if.conditions.*.devices.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap as an object
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.jsonMap' => 'nullable|array', // Validate jsonMap as an object
            'cases.*.then.delay' => 'nullable|date_format:H:i',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $condition = Condition::find($request->id);
        $newCase = $request->cases[0];
    
        // Assign a unique case ID
        $newCase['case_id'] = uniqid();
    
        // Check and handle jsonMap in the 'if' block
        foreach ($newCase['if']['conditions'] as &$conditionBlock) {
            if (isset($conditionBlock['devices']) && is_array($conditionBlock['devices'])) {
                foreach ($conditionBlock['devices'] as &$device) {
                    // If jsonMap is not set or empty, set it to an empty JSON object
                    if (empty($device['jsonMap'])) {
                        $device['jsonMap'] = null; // Set to null or '{}' as per your preference
                    }
                }
            }
        }
    
        // Check and handle jsonMap in the 'then' block
        foreach ($newCase['then']['actions'] as &$action) {
            if (isset($action['devices']) && is_array($action['devices'])) {
                foreach ($action['devices'] as &$device) {
                    // If jsonMap is not set or empty, set it to an empty JSON object
                    if (empty($device['jsonMap'])) {
                        $device['jsonMap'] = null; // Set to null or '{}' as per your preference
                    }
                }
            }
        }
    
        // Decode the existing cases, append the new case, and re-encode
        $existingCases = json_decode($condition->cases, true);
        $existingCases[] = $newCase;
    
        $condition->cases = json_encode($existingCases);
        $condition->save();
    
        // Schedule the new case
        foreach ($newCase['then']['actions'] as $action) {
            $this->scheduleAction($action, $condition->id, $newCase['case_id'], $newCase['if']['conditions'], $newCase['repetition'] ?? null);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Case added successfully and scheduled.',
            'data' => [
                [
                    'id' => $condition->id,
                    'user_id' => $condition->user_id,
                    'project_id' => $condition->project_id,
                    'cases' => json_decode($condition->cases), // Decode cases to include in the response
                ],
            ],
        ], 200);
    }    

    public function inactivateCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condition_id' => 'required|exists:conditions,id',
            'case_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $condition = Condition::find($request->condition_id);

        // Decode the cases JSON to an array
        $cases = json_decode($condition->cases, true);

        // Find the case by `case_id`
        $caseIndex = null;
        foreach ($cases as $index => $case) {
            if ($case['case_id'] === $request->case_id) {
                $caseIndex = $index;
                break;
            }
        }

        if ($caseIndex === null) {
            return response()->json([
                'status' => false,
                'message' => 'Case not found in the condition.',
            ], 404);
        }

        // Set the `is_active` flag to false for the specified case
        $cases[$caseIndex]['is_active'] = !$cases[$caseIndex]['is_active'];

        // Save the updated cases back to the condition
        $condition->cases = json_encode($cases);
        $condition->save();

        // Cancel any jobs associated with the case
        $this->cancelCaseJobs($condition->id, $request->case_id);

        return response()->json([
            'status' => true,
            'message' => 'Case inactivated successfully.',
        ], 200);
    }

    public function index($projectId)
    {
        $conditions = Condition::where('project_id', $projectId)->get();

        $parsedConditions = $conditions->map(function ($condition) {
            return [
                'id' => $condition->id,
                'user_id' => $condition->user_id,
                'project_id' => $condition->project_id,
                'cases' => json_decode($condition->cases, true),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Conditions retrieved successfully',
            'data' => $parsedConditions,
        ], 200);
    }

    public function delete($conditionId)
    {
        $condition = Condition::find($conditionId);
        if (!$condition) {
            return response()->json([
                'status' => false,
                'message' => 'Condition not found',
            ], 404);
        }

        $this->cancelRunningJobs($conditionId);
        $condition->delete();

        return response()->json([
            'status' => true,
            'message' => 'Condition and associated jobs deleted successfully',
        ], 200);
    }

    private function cancelRunningJobs($conditionId)
    {
        $jobs = JobTracker::where('condition_id', $conditionId)->get();

        foreach ($jobs as $job) {
            Log::info("Cancelling job with ID {$job->job_id} for condition {$conditionId}");
            $job->delete();
        }

        Log::info("All job tracker entries for condition {$conditionId} have been deleted.");
    }

    public function deleteSpecificJob($jobId)
    {
        $jobRecord = JobTracker::where('job_id', $jobId)->first();

        if ($jobRecord) {
            $jobRecord->delete();
            Log::info("Job with ID {$jobId} has been deleted.");
            return response()->json(['status' => true, 'message' => 'Job deleted successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Job not found'], 404);
    }

    public function deleteCase($conditionId, $caseId)
    {
        // Find the condition by ID
        $condition = Condition::find($conditionId);
        if (!$condition) {
            return response()->json([
                'status' => false,
                'message' => 'Condition not found',
            ], 404);
        }
    
        // Decode the cases JSON into an array
        $cases = json_decode($condition->cases, true);
        if (!is_array($cases)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid cases data format',
            ], 400);
        }
    
        // Find the case index by its ID
        $caseIndex = null;
        foreach ($cases as $index => $case) {
            if ($case['case_id'] === $caseId) {
                $caseIndex = $index;
                break;
            }
        }
    
        // If the case is not found, return an error response
        if ($caseIndex === null) {
            return response()->json([
                'status' => false,
                'message' => 'Case not found',
            ], 404);
        }
    
        // If this is the only case, delete the condition
        if (count($cases) === 1) {
            $this->cancelCaseJobs($conditionId, $caseId); // Cancel jobs related to the case
            $condition->delete(); // Delete the condition itself
    
            return response()->json([
                'status' => true,
                'message' => 'Condition and its only case deleted successfully',
            ], 200);
        }
    
        // Otherwise, remove the specific case from the cases array
        array_splice($cases, $caseIndex, 1);
        $this->cancelCaseJobs($conditionId, $caseId); // Cancel jobs related to the case
    
        // Save the updated cases back to the condition
        $condition->cases = json_encode($cases);
        $condition->save();
    
        return response()->json([
            'status' => true,
            'message' => 'Case and associated jobs deleted successfully',
            'data' => [
                [
                    'id' => $condition->id,
                    'user_id' => $condition->user_id,
                    'project_id' => $condition->project_id,
                    'cases' => json_decode($condition->cases), // Decode cases to include in the response
                ],
            ],
        ], 200);
    }    

    private function cancelCaseJobs($conditionId, $caseId)
    {
        $jobs = JobTracker::where('condition_id', $conditionId)
            ->where('case_id', $caseId)
            ->get();

        foreach ($jobs as $job) {
            Log::info("Cancelling job with ID {$job->job_id} for case {$caseId} in condition {$conditionId}");
            $job->delete();
        }

        Log::info("All job tracker entries for case {$caseId} in condition {$conditionId} have been deleted.");
    }
}
