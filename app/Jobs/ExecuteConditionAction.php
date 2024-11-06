<?php

namespace App\Jobs;

use App\Models\Condition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteConditionAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conditionId;
    public $action;

    public function __construct($conditionId, $action)
    {
        $this->conditionId = $conditionId;
        $this->action = $action;
    }

    public function handle()
    {
        // Fetch the condition and execute the action
        $condition = Condition::find($this->conditionId);
        if ($condition) {
            foreach ($this->action['devices'] as $device) {
                // Implement device action logic here
                // Example: Control device based on $device['device_id'] and $device['action']
            }
        }
    }
}

