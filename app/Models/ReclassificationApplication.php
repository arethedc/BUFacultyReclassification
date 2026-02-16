<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationApplication extends Model
{
    protected $fillable = [
        'faculty_user_id',
        'period_id',
        'cycle_year',
        'status',
        'current_step',
        'returned_from',
        'submitted_at',
        'finalized_at',
        'current_rank_label_at_approval',
        'approved_rank_label',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_user_id');
    }

    public function sections()
    {
        return $this->hasMany(ReclassificationSection::class, 'reclassification_application_id');
    }

    public function period()
    {
        return $this->belongsTo(ReclassificationPeriod::class, 'period_id');
    }

    public function rowComments()
    {
        return $this->hasMany(ReclassificationRowComment::class, 'reclassification_application_id');
    }

    public function moveRequests()
    {
        return $this->hasMany(ReclassificationMoveRequest::class, 'reclassification_application_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
