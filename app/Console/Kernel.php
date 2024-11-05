<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Condition;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $conditions = Condition::all();

        foreach ($conditions as $condition) {
            $cases = json_decode($condition->cases, true);

            foreach ($cases as $case) {
                foreach ($case['then'] as $action) {
                    if ($action['repetition'] === null) {
                        // Skip as these are scheduled directly for one-time execution
                        continue;
                    }

                    $time = $action['time'] ?? '00:00';

                    switch ($action['repetition']) {
                        case 'every_day':
                            $schedule->command('process:scheduled-actions', [
                                'project_id' => $condition->project_id,
                                'case_id' => $case['id']
                            ])->dailyAt($time);
                            break;

                        case 'every_week':
                            $schedule->command('process:scheduled-actions', [
                                'project_id' => $condition->project_id,
                                'case_id' => $case['id']
                            ])->weeklyOn(1, $time);  // 1 = Monday, adjust as needed
                            break;

                        case 'every_month':
                            $schedule->command('process:scheduled-actions', [
                                'project_id' => $condition->project_id,
                                'case_id' => $case['id']
                            ])->monthlyOn(1, $time);  // 1 = First day of the month
                            break;
                    }
                }
            }
        }
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
