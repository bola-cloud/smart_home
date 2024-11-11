<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTracker extends Model
{
    use HasFactory;
    // Define the table name if it's not following Laravel's naming convention
    protected $table = 'job_trackers';

    // Define the fillable attributes
    protected $fillable = [
        'job_id',
        'condition_id',
        'case_id',
        'status',
    ];

    /**
     * Get the condition associated with the job.
     */
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }
}
