<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Opportunity extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'customer_id',
        'lead_id',
        'title',
        'description',
        'stage',
        'loss_reason',
        'competitor_name',
        'loss_notes',
        'estimated_value',
        'expected_start_date',
        'expected_close_date',
        'assigned_to',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_start_date' => 'date:Y-m-d',
        'expected_close_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Customer associated with this opportunity.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Originating Lead for this opportunity (if converted from a lead).
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * User assigned to handle this opportunity.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who created this opportunity.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Site visits conducted for this opportunity.
     */
    public function siteVisits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }

    /**
     * Engineering measurements conducted for this opportunity.
     */
    public function measurements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    /**
     * Scope query by stage.
     */
    public function scopeStage(Builder $query, string $stage): Builder
    {
        return $query->where('stage', $stage);
    }

    /**
     * Scope query by customer.
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope query by assigned user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Configure Spatie Activity Logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('commercial')
            ->logOnly([
                'title',
                'customer_id',
                'lead_id',
                'stage',
                'estimated_value',
                'expected_start_date',
                'expected_close_date',
                'assigned_to',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.opportunity_created', ['title' => $this->title]),
                'updated' => __('activity.opportunity_updated', ['title' => $this->title]),
                'deleted' => __('activity.opportunity_deleted', ['title' => $this->title]),
                default => __('activity.opportunity_event', ['event' => $eventName, 'title' => $this->title]),
            });
    }

    /**
     * Register Spatie Media Library Collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }
}
