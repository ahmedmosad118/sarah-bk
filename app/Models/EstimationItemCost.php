<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimationItemCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimation_item_id',
        'cost_type',
        'description',
        'cost_rate',
        'amount',
        'notes',
    ];

    protected $casts = [
        'cost_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent estimation line.
     */
    public function estimationItem(): BelongsTo
    {
        return $this->belongsTo(EstimationItem::class);
    }
}
