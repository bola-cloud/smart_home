<?php

namespace App\Http\Controllers\Api\Conditions;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Jobs\ExecuteConditionAction;

class ConditionsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'cases' => 'required|array',
            'cases.*.name' => 'required|string|max:256',  // Ensure the name is included for each case
            'cases.*.is_active' => 'nullable|boolean',   // Ensure is_active is included
            'cases.*.case_id' => 'required|string|max:256', // Ensure case_id is included

            // Global `if` conditions with logic
            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'nullable|exists:components,id',
            'cases.*.if.conditions.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.type' => 'nullable|string|in:sunrise,sunset', // Validation for type
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',
        
            // Global `then` actions with logic
            'cases.*.then.actions' => 'required|array',
            'cases.*.then.logic' => 'required|string|in:AND,OR',
            'cases.*.then.actions.*.devices' => 'required|array|min:1',
            'cases.*.then.actions.*.devices.*.component_id' => 'required|exists:components,id',
            'cases.*.then.actions.*.devices.*.action' => 'required|string',
            'cases.*.then.delay' => 'nullable|date_format:H:i', // Delay in HH:mm format
            'cases.*.then.actions.*.repetition' => 'nullable|string|in:every_day,every_week,every_month',

            'is_active' => 'nullable|boolean', // Make sure is_active is set
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
            foreach ($case['then'] as $action) {
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
        // Check if time is provided in the action
        if (!empty($action['time'])) {
            $actionTime = Carbon::parse($action['time']);
            $initialDelay = Carbon::now()->diffInSeconds($actionTime, false);

            if ($initialDelay < 0) {
                // If the time has already passed today, add 24 hours for next day
                $initialDelay += 86400;
            }

            ExecuteConditionAction::dispatch($conditionId, $action)
                ->delay(now()->addSeconds($initialDelay));
        } elseif (!empty($action['delay'])) {
            // If delay is provided, schedule the action after the specified delay
            $delay = Carbon::parse($action['delay']);
            ExecuteConditionAction::dispatch($conditionId, $action)
                ->delay(now()->addMinutes($delay->minute)); // Assuming delay is in minutes
        } else {
            // If no time or delay is specified, execute immediately
            ExecuteConditionAction::dispatch($conditionId, $action)
                ->delay(now());
        }
    }

    public function index($projectId)
    {
        $conditions = Condition::where('project_id', $projectId)->get();

        $parsedConditions = $conditions->map(function ($condition) {
            return [
                'id' => $condition->id,
                'user_id' => $condition->user_id,
                'project_id' => $condition->project_id,
                'cases' => json_decode($condition->cases, true), // Decode JSON data
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
        // Find and delete the jobs associated with the condition
        $jobs = Queue::getJobsFromQueue('default'); // Assuming default queue
    
        foreach ($jobs as $job) {
            // Check if the job is related to the condition
            if ($job->conditionId == $conditionId) {
                $job->delete(); // This will delete the job if it's pending
                Log::info("Job for condition {$conditionId} has been cancelled.");
            }
        }
    }    
}
