<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Condition;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Retrieve all conditions with cases and set up the schedule
        Condition::all()->each(function ($condition) use ($schedule) {
            $cases = json_decode($condition->cases, true);

            foreach ($cases as $case) {
                foreach ($case['then'] as $action) {
                    $time = $action['time'] ?? '00:00';
                    $command = "process:scheduled-actions {$condition->project_id} {$case['id']}";

                    // Check if repetition key exists and handle accordingly
                    $repetition = $action['repetition'] ?? null;

                    switch ($repetition) {
                        case 'every_day':
                            $schedule->command($command)->dailyAt($time);
                            break;

                        case 'every_week':
                            $schedule->command($command)->weeklyOn(1, $time);  // Every Monday, adjust as needed
                            break;

                        case 'every_month':
                            $schedule->command($command)->monthlyOn(1, $time); // First day of the month
                            break;

                        case null:
                            // For one-time actions without repetition, schedule it once if it hasn't passed
                            if (Carbon::now()->lte(Carbon::parse($time))) {
                                $schedule->command($command)->at($time);
                            }
                            break;
                    }
                }
            }
        });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
