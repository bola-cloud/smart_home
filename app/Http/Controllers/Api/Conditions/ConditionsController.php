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
use Illuminate\Support\Str; // For unique ID generation

class ConditionsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'cases' => 'required|array',
            'cases.*.name' => 'required|string|max:256',
            'cases.*.is_active' => 'nullable|boolean',
            'cases.*.case_id' => 'required|string|max:256',
            // Global `if` conditions with logic
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'nullable|exists:components,id',
            'cases.*.if.conditions.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.type' => 'nullable|string|in:sunrise,sunset',
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
            // Global `then` actions with logic
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.logic' => 'required|string|in:AND,OR',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.action' => 'required|string',
            'cases.*.then.delay' => 'nullable|date_format:H:i',
            'cases.*.then.actions.*.repetition' => 'nullable|string|in:every_day,every_week,every_month',
            'is_active' => 'nullable|boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $user = Auth::user();
        $cases = $request->cases;
    
        // Add unique ID for each case and save the name, is_active, and case_id
        foreach ($cases as &$case) {
            $case['case_id'] = uniqid();  // Assign unique case ID
        }
    
        // Store the condition in the database
        $condition = Condition::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'cases' => json_encode($cases),
        ]);
    
        // Schedule actions based on "then" for each case
        foreach ($cases as $case) {
            foreach ($case['then']['actions'] as $action) {
                // Call the function to handle scheduling the action with proper time checks
                $this->scheduleAction($action, $condition->id);
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Condition created successfully with schedules',
        ], 200);
    }
    
    private function scheduleAction($action, $conditionId)
    {
        // Generate a unique job ID
        $jobId = Str::uuid()->toString();
    
        if (!empty($action['time'])) {
            $actionTime = Carbon::parse($action['time']);
            $initialDelay = Carbon::now()->diffInSeconds($actionTime, false);
    
            if ($initialDelay < 0) {
                $initialDelay += 86400; // Add 24 hours if the time has already passed today
            }

            $job = ExecuteConditionAction::dispatch($conditionId, $action)
                ->delay(now()->addSeconds($initialDelay));
    
        } elseif (!empty($action['delay'])) {
            $delay = Carbon::parse($action['delay']);
            $job = ExecuteConditionAction::dispatch($conditionId, $action)
                ->delay(now()->addMinutes($delay->minute));
    
        } else {
            $job = ExecuteConditionAction::dispatch($conditionId, $action);
        }
    
        // Store the job ID in JobTracker for later access
        JobTracker::create([
            'job_id' => $jobId,
            'condition_id' => $conditionId,
        ]);

        Log::info("Scheduled job with ID {$jobId} for condition {$conditionId}");
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
        // Find the condition
        $condition = Condition::find($conditionId);
        if (!$condition) {
            return response()->json([
                'status' => false,
                'message' => 'Condition not found',
            ], 404);
        }

        // Cancel any running jobs associated with this condition
        $this->cancelRunningJobs($conditionId);

        // Delete the condition from the database
        $condition->delete();

        return response()->json([
            'status' => true,
            'message' => 'Condition and associated jobs deleted successfully',
        ], 200);
    }

    private function cancelRunningJobs($conditionId)
    {
        // Get all jobs associated with this condition
        $jobs = JobTracker::where('condition_id', $conditionId)->get();

        foreach ($jobs as $job) {
            // As Laravel doesn’t support direct job cancellation by ID in the queue,
            // we rely on marking the job for deletion if you have a custom job delete handler
            Log::info("Job with ID {$job->job_id} for condition {$conditionId} has been marked for cancellation.");
        }

        // Optionally, delete the job records from the database
        JobTracker::where('condition_id', $conditionId)->delete();
    }

    public function deleteSpecificJob($jobId)
    {
        // Optionally implement specific logic here to mark the job as canceled
        // Since direct removal from the queue might not work for dispatched jobs
        $jobRecord = JobTracker::where('job_id', $jobId)->first();
        
        if ($jobRecord) {
            $jobRecord->delete();
            Log::info("Job with ID {$jobId} has been deleted.");
            return response()->json(['status' => true, 'message' => 'Job deleted successfully']);
        }
        
        return response()->json(['status' => false, 'message' => 'Job not found'], 404);
    }
}
