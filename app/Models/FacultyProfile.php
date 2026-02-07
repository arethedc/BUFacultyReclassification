<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacultyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_no',
        'employment_type',
        'teaching_rank',
        'rank_step',
        'original_appointment_date',
    ];

    protected $casts = [
        'original_appointment_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
