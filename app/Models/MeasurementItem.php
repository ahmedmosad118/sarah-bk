<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'room_name',
        'item_name',
        'unit',
        'measurement_type',
        'count',
        'length',
        'width',
        'height',
        'deductions',
        'gross_quantity',
        'net_quantity',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'count' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'deductions' => 'decimal:2',
        'gross_quantity' => 'decimal:2',
        'net_quantity' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Measurement.
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }
}
