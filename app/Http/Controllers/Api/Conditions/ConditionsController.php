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
        $repetition = $action['repetition'];
    
        // Dispatch a job based on the repetition type
        switch ($repetition) {
            case 'every_day':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->dailyAt($actionTime->format('H:i'));
                break;
            case 'every_week':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->weeklyOn($actionTime->dayOfWeek, $actionTime->format('H:i'));
                break;
            case 'every_month':
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->monthlyOn($actionTime->day, $actionTime->format('H:i'));
                break;
            case null:
                // Schedule as one-time if repetition is null
                ExecuteConditionAction::dispatch($conditionId, $action)
                    ->delay($actionTime);
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
