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
            'cases.*.repetition.*' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.if.conditions.*.devices.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.action' => 'required|array',
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
    
        // Add unique IDs for each case
        foreach ($cases as &$case) {
            $case['case_id'] = uniqid();
        }
    
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

    public function editCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condition_id' => 'required|exists:conditions,id',
            'case_id' => 'required|string',
            'case.name' => 'required|string|max:256',
            'case.is_active' => 'nullable|boolean',
            'case.repetition' => 'nullable|array',
            'case.repetition.*' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'case.if.conditions' => 'required|array',
            'case.if.logic' => 'required|string|in:AND,OR',
            'case.if.conditions.*.devices' => 'nullable|array',
            'case.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'case.if.conditions.*.devices.*.status' => 'nullable|string',
            'case.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            'case.then.actions' => 'required|array',
            'case.then.actions.*.devices' => 'required|array|min:1',
            'case.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'case.then.actions.*.devices.*.action' => 'required|array',
            'case.then.delay' => 'nullable|date_format:H:i',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $condition = Condition::find($request->condition_id);
        $updatedCase = $request->case;
    
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
                'condition_id' => $condition->id,
                'cases' => $existingCases,
            ],
        ], 200);
    }    

    public function addCase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'condition_id' => 'required|exists:conditions,id',
            'case.name' => 'required|string|max:256',
            'case.is_active' => 'nullable|boolean',
            'case.repetition' => 'nullable|array',
            'case.repetition.*' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'case.if.conditions' => 'required|array',
            'case.if.logic' => 'required|string|in:AND,OR',
            'case.if.conditions.*.devices' => 'nullable|array',
            'case.if.conditions.*.devices.*.component_id' => 'required|exists:components,id',
            'case.if.conditions.*.devices.*.status' => 'nullable|string',
            'case.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            'case.then.actions' => 'required|array',
            'case.then.actions.*.devices' => 'required|array|min:1',
            'case.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'case.then.actions.*.devices.*.action' => 'required|array',
            'case.then.delay' => 'nullable|date_format:H:i',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $condition = Condition::find($request->condition_id);
        $newCase = $request->case;
    
        // Assign a unique case ID
        $newCase['case_id'] = uniqid();
    
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
            'data' => $condition, // Return the entire condition, including cases
        ], 200);
    }
    

    private function scheduleAction($action, $conditionId, $caseId, $ifConditions, $repetitionDays = null)
    {
        $jobId = Str::uuid()->toString();
        $action['case_id'] = $caseId;
        $scheduledTime = null;

        foreach ($ifConditions as $condition) {
            if (!empty($condition['time'])) {
                $scheduledTime = Carbon::parse($condition['time']);
                break;
            }
        }

        if ($scheduledTime) {
            $currentTime = Carbon::now();
            $delayInSeconds = $currentTime->diffInSeconds($scheduledTime, false);

            if ($delayInSeconds < 0) {
                $delayInSeconds += 86400;
            }

            $job = ExecuteConditionAction::dispatch($conditionId, $caseId, $repetitionDays)
                ->delay(now()->addSeconds($delayInSeconds));
        } else {
            $job = ExecuteConditionAction::dispatch($conditionId, $caseId, $repetitionDays);
        }

        JobTracker::create([
            'job_id' => $jobId,
            'condition_id' => $conditionId,
            'case_id' => $caseId,
        ]);

        Log::info("Scheduled job with ID {$jobId} for case {$caseId} in condition {$conditionId}");
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
