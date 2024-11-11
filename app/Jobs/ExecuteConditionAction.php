<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
use App\Services\MqttService;
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
    protected $jobId;

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;
    }

    public function handle()
    {
        $condition = Condition::find($this->conditionId);

        if ($condition && !$condition->isCaseActive($this->action['case_id'])) {
            Log::info("Job for case {$this->action['case_id']} is inactive and will not execute.");
            return; // Exit without executing the job
        }

        $ifLogic = $condition->cases['if']['logic'];
        $ifConditions = $condition->cases['if']['conditions'];

        if ($this->evaluateIfConditions($ifConditions, $ifLogic)) {
            if (isset($this->action['devices']) && is_array($this->action['devices'])) {
                foreach ($this->action['devices'] as $device) {
                    $componentState = $this->checkComponentState($device['component_id']);
                    if ($componentState === $device['status']) {
                        $this->executeAction($device);
                    }
                }
            } else {
                Log::error("No devices provided in the 'then' actions for condition {$this->conditionId}");
            }
        }

        $this->scheduleNext();
    }

    private function evaluateIfConditions($conditions, $logic)
    {
        $results = [];
        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($condition);
            $results[] = $result;
        }

        return $logic === 'AND' ? !in_array(false, $results) : in_array(true, $results);
    }

    private function evaluateSingleCondition($condition)
    {
        $mqttService = new MqttService();

        foreach ($condition['devices'] as $device) {
            $componentState = $mqttService->getLastState($device['component_id']);
            if ($componentState === null || $componentState != $device['status']) {
                return false;
            }
        }

        if (!empty($condition['time'])) {
            $conditionTime = Carbon::parse($condition['time']);
            if (!$conditionTime->equalTo(Carbon::now())) {
                return false;
            }
        }

        return true;
    }

    private function checkComponentState($componentId)
    {
        $mqttService = new MqttService();
        return $mqttService->getLastState($componentId);
    }

    private function executeAction($device)
    {
        $component = Component::find($device['component_id']);
        if ($component) {
            $component->update(['type' => $device['action']]);
            Log::info("Executed action: {$device['action']} on component: {$device['component_id']}");
        }
    }

    private function scheduleNext()
    {
        $repetition = $this->action['repetition'] ?? null;
        if (!$repetition) return;

        $nextExecution = match ($repetition) {
            'every_day' => Carbon::now()->addDay(),
            'every_week' => Carbon::now()->addWeek(),
            'every_month' => Carbon::now()->addMonth(),
            default => null,
        };

        if ($nextExecution) {
            ExecuteConditionAction::dispatch($this->conditionId, $this->action)
                ->delay($nextExecution);
        }
    }
}
