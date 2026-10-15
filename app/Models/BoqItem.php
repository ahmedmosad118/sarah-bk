<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'boq_id',
        'scope_item_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent BOQ document.
     */
    public function boq(): BelongsTo
    {
        return $this->belongsTo(Boq::class);
    }

    /**
     * Source scope work item this quantity line was derived from.
     */
    public function scopeItem(): BelongsTo
    {
        return $this->belongsTo(ScopeItem::class);
    }

    /**
     * Estimation items costing this BOQ item.
     */
    public function estimationItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EstimationItem::class);
    }
}
