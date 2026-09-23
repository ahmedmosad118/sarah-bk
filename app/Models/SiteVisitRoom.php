<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisitRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_visit_id',
        'room_name',
        'estimated_area',
        'notes',
    ];

    protected $casts = [
        'estimated_area' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Site Visit for this room.
     */
    public function siteVisit(): BelongsTo
    {
        return $this->belongsTo(SiteVisit::class);
    }
}
