<?php

namespace App\Jobs;

use App\Models\Condition;
use App\Models\Component;
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
                $component = Component::find($device->id);
                if ($component) {
                    $component->update([
                        'type' => "bola",
                    ]);
                }
                // Implement device action logic here
                // Example: Control device based on $device['device_id'] and $device['action']
            }
        }
    }
}

