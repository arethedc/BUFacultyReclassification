<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'order_no',
    ];

    public function facultyProfiles(): HasMany
    {
        return $this->hasMany(FacultyProfile::class);
    }
}
