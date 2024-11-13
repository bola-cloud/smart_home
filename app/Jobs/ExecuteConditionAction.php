<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteConditionAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $action;

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;

        Log::info("Job created for condition {$conditionId} with action: ", $action);
    }

    public function handle()
    {
        Log::info("Job handling started for condition {$this->conditionId}   bola");
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        Log::info("Forcing evaluateIfConditions to return true for testing purposes.");
        return true;
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => "bola2"]);
            Log::info("Executed action on component ID {$device['component_id']}");
        } else {
            Log::error("Failed to find component with ID {$device['component_id']} for action execution");
        }
    }

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'] ?? null;
        if (!$repetition) {
            Log::info("No repetition specified, job will not be rescheduled");
            return;
        }

        $nextExecution = match ($repetition) {
            'every_day' => Carbon::now()->addDay(),
            'every_week' => Carbon::now()->addWeek(),
            'every_month' => Carbon::now()->addMonth(),
            default => null,
        };

        if ($nextExecution) {
            Log::info("Scheduling next execution for condition {$this->conditionId} at {$nextExecution}");
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        } else {
            Log::error("Invalid repetition interval specified: {$repetition}");
        }
    }
}
