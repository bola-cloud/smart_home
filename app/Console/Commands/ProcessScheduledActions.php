<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Condition;
use App\Services\MqttService;
use Carbon\Carbon;

class ProcessScheduledActions extends Command
{
    protected $signature = 'process:scheduled-actions {project_id} {case_id}';
    protected $description = 'Process and execute scheduled actions for smart home cases';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $projectId = $this->argument('project_id');
        $caseId = $this->argument('case_id');

        $condition = Condition::where('project_id', $projectId)->first();

        if (!$condition) {
            $this->error('Condition not found');
            return;
        }

        $cases = json_decode($condition->cases, true);
        $case = collect($cases)->firstWhere('id', $caseId);

        if (!$case) {
            $this->error('Case not found');
            return;
        }

        // Check "if" conditions
        foreach ($case['if'] as $ifCondition) {
            if (!$this->evaluateIfCondition($ifCondition)) {
                $this->info('Condition not met, skipping action');
                return;
            }
        }

        // Execute "then" actions
        $mqttService = new MqttService();
        $mqttService->connect();

        foreach ($case['then'] as $thenAction) {
            foreach ($thenAction['devices'] as $deviceAction) {
                $mqttService->publishAction(
                    $deviceAction['device_id'],
                    $deviceAction['action']
                );
            }
        }

        $mqttService->disconnect();
    }

    protected function evaluateIfCondition($ifCondition)
    {
        // Check time condition
        if (isset($ifCondition['time']) && $ifCondition['time'] !== Carbon::now()->format('H:i')) {
            return false;
        }

        // Check device conditions
        if (isset($ifCondition['devices'])) {
            foreach ($ifCondition['devices'] as $deviceCondition) {
                // Stub: replace with real device status check
                if ($deviceCondition['status'] !== 'on') {
                    return false;
                }
            }
        }

        return true;
    }
}
