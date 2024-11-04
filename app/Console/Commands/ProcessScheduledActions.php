<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Condition;

class ProcessScheduledActions extends Command
{
    protected $signature = 'process:scheduled-actions';
    protected $description = 'Process scheduled actions for all conditions based on their time and repetition';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $currentTime = Carbon::now();

        // Fetch all conditions
        $conditions = Condition::all();
        
        foreach ($conditions as $condition) {
            $cases = json_decode($condition->cases, true);

            foreach ($cases as $case) {
                foreach ($case['then'] as $thenAction) {
                    // Determine if the action should be triggered based on repetition and time
                    $actionTime = Carbon::parse($thenAction['time']);
                    $repetition = $thenAction['repetition'];

                    if ($this->shouldTriggerAction($currentTime, $actionTime, $repetition)) {
                        // Execute the action for the devices specified
                        foreach ($thenAction['devices'] as $deviceAction) {
                            $this->triggerDeviceAction($deviceAction);
                        }
                    }
                }
            }
        }
    }

    protected function shouldTriggerAction($currentTime, $actionTime, $repetition)
    {
        switch ($repetition) {
            case 'every_day':
                return $currentTime->isSameMinute($actionTime);
            case 'every_week':
                return $currentTime->isSameMinute($actionTime) && $currentTime->isSameDayOfWeek($actionTime);
            case 'every_month':
                return $currentTime->isSameMinute($actionTime) && $currentTime->day === $actionTime->day;
            default:
                return false;
        }
    }

    protected function triggerDeviceAction($deviceAction)
    {
        // Logic to execute action for a device, such as turning it on or off
        // This could involve updating device status in the database, sending a message, etc.
    }
}

