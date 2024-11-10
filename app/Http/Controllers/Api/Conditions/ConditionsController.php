<?php

namespace App\Http\Controllers\Api\Conditions;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\JobTracker; // Make sure to use the JobTracker model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Jobs\ExecuteConditionAction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

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

            'cases.*.if.conditions' => 'required|array',
            'cases.*.if.logic' => 'required|string|in:AND,OR',
            'cases.*.if.conditions.*.devices' => 'nullable|array',
            'cases.*.if.conditions.*.devices.*.component_id' => 'nullable|exists:components,id',
            'cases.*.if.conditions.*.status' => 'nullable|string',
            'cases.*.if.conditions.*.type' => 'nullable|string|in:sunrise,sunset',
            'cases.*.if.conditions.*.time' => 'nullable|date_format:Y-m-d H:i',

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
        // Dispatch the job and store the job ID
        $actionTime = Carbon::parse($action['time']);
        $initialDelay = Carbon::now()->diffInSeconds($actionTime, false);

        if ($initialDelay < 0) {
            $initialDelay += 86400;
        }

        $job = ExecuteConditionAction::dispatch($conditionId, $action)
            ->delay(now()->addSeconds($initialDelay));

        // Store the job ID in the JobTracker table
        JobTracker::create([
            'job_id' => $job->getJobId(),
            'condition_id' => $conditionId,
        ]);
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
            // Find and cancel the job
            $job = Queue::getJobById($job->job_id);

            if ($job) {
                $job->delete();  // Cancel the job if it is in the queue
                Log::info("Job with ID {$job->job_id} for condition {$conditionId} has been cancelled.");
            }
        }

        // Optionally, delete the job records from the database
        JobTracker::where('condition_id', $conditionId)->delete();
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
}
