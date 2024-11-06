<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Condition;
use App\Models\Component;
use App\Services\MqttService;
use Carbon\Carbon;

class ProcessScheduledActions extends Command
{
    protected $signature = 'conditions:process';
    protected $description = 'Process scheduled actions based on conditions';

    public function handle()
    {
        $currentDateTime = Carbon::now();

        // Fetch all conditions
        $conditions = Condition::all();

        foreach ($conditions as $condition) {
            foreach (json_decode($condition->cases, true) as $case) {
                foreach ($case['then'] as $action) {
                    $actionTime = Carbon::parse($action['time']);
                    
                    if ($this->shouldExecute($action, $currentDateTime, $actionTime)) {
                        $this->executeAction($action['devices']);  // Function to perform the actual action
                    }
                }
            }
        }
    }

    private function shouldExecute($action, $currentDateTime, $actionTime)
    {
        $repetition = $action['repetition'];

        switch ($repetition) {
            case 'every_day':
                return $currentDateTime->isSameTime($actionTime);
            case 'every_week':
                return $currentDateTime->isSameTime($actionTime) && $currentDateTime->isSameDayOfWeek($actionTime);
            case 'every_month':
                return $currentDateTime->isSameTime($actionTime) && $currentDateTime->day == $actionTime->day;
            case null:
                return $currentDateTime->equalTo($actionTime);
            default:
                return false;
        }
    }

    private function executeAction($devices)
    {
        // Logic to perform the action, such as turning devices on/off
        foreach ($devices as $device) {
            // Implement action logic here based on $device['device_id'] and $device['action']
        }
    }
}
