<?php

namespace App\Http\Controllers\Api\Conditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Condition;
use App\Console\Commands\ProcessScheduledActions;
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
    
        // Schedule actions for each "then" condition
        foreach ($cases as $case) {
            foreach ($case['then'] as $action) {
                // dd($action);
                $this->scheduleAction($action, $case['id'], $request->project_id);
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Condition created successfully with schedules',
        ], 200);
    }
    
    protected function scheduleAction($action, $caseId, $projectId)
    {
        $scheduledTime = Carbon::parse($action['time']);
    
        if ($action['repetition'] === null) {
            // For one-time action with no repetition, schedule it once at the specified time
            ProcessScheduledActions::dispatch($projectId, $caseId)
                ->delay($scheduledTime->diffInSeconds(Carbon::now()));
        } else {
            // Use Laravel scheduler for recurring tasks
            Artisan::call('schedule:setup', [
                'project_id' => $projectId,
                'case_id' => $caseId,
                'repetition' => $action['repetition'],
                'time' => $action['time'],
            ]);
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
