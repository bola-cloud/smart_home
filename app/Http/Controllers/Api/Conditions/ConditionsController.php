<?php

namespace App\Http\Controllers\Api\Conditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Condition;
use App\Jobs\ExecuteConditionAction; // Create a Job for executing the actions
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class ConditionsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'cases' => 'required|array',
            'cases.*.if' => 'required|array',
            'cases.*.if.*.devices' => 'nullable|array',
            'cases.*.if.*.devices.*.device_id' => 'nullable|exists:devices,id',
            'cases.*.if.*.devices.*.status' => 'nullable|string',
            'cases.*.if.*.time' => 'nullable|date_format:H:i',
            'cases.*.if.*.logic' => 'required|string|in:AND,OR',
            'cases.*.then' => 'required|array',
            'cases.*.then.*.devices' => 'required|array|min:1',
            'cases.*.then.*.devices.*.device_id' => 'required|exists:devices,id',
            'cases.*.then.*.devices.*.action' => 'required|string',
            'cases.*.then.*.time' => 'nullable|date_format:H:i',
            'cases.*.then.*.repetition' => 'nullable|string|in:every_day,every_week,every_month',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        $user = Auth::user();
        $cases = $request->cases;
    
        // Add unique ID for each case
        foreach ($cases as &$case) {
            $case['id'] = 'case_' . uniqid();
        }
    
        $condition = Condition::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'cases' => json_encode($cases),
        ]);
    
        // Schedule each action in "then" based on "time" and "repetition"
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
        $actionTime = Carbon::parse($action['time']);
        $now = Carbon::now();
        $repetition = $action['repetition'];
    
        // Calculate the delay until the first scheduled time
        $delay = $now->diffInSeconds($actionTime, false);
        if ($delay < 0) {
            // If the time has already passed today, add 24 hours for the next day
            $delay += 86400;
        }
    
        // Dispatch the job based on repetition
        ExecuteConditionAction::dispatch($conditionId, $action)
            ->delay(now()->addSeconds($delay));
    
        // Schedule future repetitions if needed
        if ($repetition) {
            $this->scheduleRepetitions($conditionId, $action, $repetition);
        }
    }
    
    private function scheduleRepetitions($conditionId, $action, $repetition)
    {
        switch ($repetition) {
            case 'every_day':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->delay(now()->addDay());
                break;
    
            case 'every_week':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->delay(now()->addWeek());
                break;
    
            case 'every_month':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->delay(now()->addMonth());
                break;
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
}
