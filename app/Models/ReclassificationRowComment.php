<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclassificationRowComment extends Model
{
    protected $fillable = [
        'reclassification_application_id',
        'reclassification_section_entry_id',
        'user_id',
        'body',
        'visibility',
    ];

    public function application()
    {
        return $this->belongsTo(ReclassificationApplication::class, 'reclassification_application_id');
    }

    public function entry()
    {
        return $this->belongsTo(ReclassificationSectionEntry::class, 'reclassification_section_entry_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
