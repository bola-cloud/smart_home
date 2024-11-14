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
        $cases = $request->cases;

        foreach ($cases as &$case) {
            $case['case_id'] = uniqid();
        }

        $condition = Condition::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'cases' => json_encode($cases),
        ]);

        foreach ($cases as $case) {
            $ifConditions = $case['if']['conditions'];
            foreach ($case['then']['actions'] as $action) {
                $this->scheduleAction($action, $condition->id, $case['case_id'], $ifConditions, $case['repetition'] ?? null);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Condition created successfully with schedules',
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
        $condition = Condition::find($conditionId);
        if (!$condition) {
            return response()->json([
                'status' => false,
                'message' => 'Condition not found',
            ], 404);
        }

        $cases = json_decode($condition->cases, true);
        $caseIndex = null;
        foreach ($cases as $index => $case) {
            if ($case['case_id'] === $caseId) {
                $caseIndex = $index;
                break;
            }
        }

        if ($caseIndex === null) {
            return response()->json([
                'status' => false,
                'message' => 'Case not found',
            ], 404);
        }

        array_splice($cases, $caseIndex, 1);
        $this->cancelCaseJobs($conditionId, $caseId);

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

    public function cancelDelayedJob($conditionId, $caseId)
    {
        $jobTracker = JobTracker::where('condition_id', $conditionId)
            ->where('case_id', $caseId)
            ->first();

        if (!$jobTracker) {
            return response()->json(['status' => false, 'message' => 'Job not found'], 404);
        }

        $jobTracker->update(['status' => 'canceled']);
        Log::info("Job with ID {$jobTracker->job_id} for case {$caseId} in condition {$conditionId} has been marked as canceled.");

        return response()->json(['status' => true, 'message' => 'Job canceled successfully'], 200);
    }
}
