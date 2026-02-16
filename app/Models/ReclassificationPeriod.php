<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationPeriod extends Model
{
    protected $fillable = [
        'name',
        'cycle_year',
        'is_open',
        'start_at',
        'end_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function applications()
    {
        return $this->hasMany(ReclassificationApplication::class, 'period_id');
    }
}
