<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScopeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope_id',
        'trade_category',
        'item_name',
        'specification',
        'inclusions',
        'exclusions',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Scope of Work.
     */
    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }

    /**
     * Linked engineering measurement items covered by this work specification.
     */
    public function measurementItems(): BelongsToMany
    {
        return $this->belongsToMany(MeasurementItem::class, 'scope_item_measurement_item')
            ->withTimestamps();
    }
}
