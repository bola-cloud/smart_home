<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Condition extends Model
{
    use HasFactory;
    protected $fillable = [
        'cases',
        'user_id',
        'project_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    //  Check the case activation  
    public function isCaseActive($caseId)
    {
        $cases = json_decode($this->cases, true);

        foreach ($cases as $case) {
            if ($case['case_id'] === $caseId) {
                return $case['is_active'] ?? true; // Default to true if not set
            }
        }

        return false; // If case not found, assume inactive
    }

}
