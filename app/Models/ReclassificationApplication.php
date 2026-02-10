<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationApplication extends Model
{
    protected $fillable = [
        'faculty_user_id',
        'cycle_year',
        'status',
        'current_step',
        'returned_from',
        'submitted_at',
        'finalized_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_user_id');
    }

    public function sections()
    {
        return $this->hasMany(ReclassificationSection::class, 'reclassification_application_id');
    }

    public function rowComments()
    {
        return $this->hasMany(ReclassificationRowComment::class, 'reclassification_application_id');
    }
}
