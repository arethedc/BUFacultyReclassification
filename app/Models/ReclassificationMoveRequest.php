<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationMoveRequest extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'source_section_code',
        'source_criterion_key',
        'target_section_code',
        'target_criterion_key',
        'note',
        'requested_by_user_id',
        'status',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}

