<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Lead extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'customer_id',
        'title',
        'description',
        'source',
        'status',
        'loss_reason',
        'competitor_name',
        'loss_notes',
        'estimated_value',
        'expected_start_date',
        'assigned_to',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_start_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Customer to whom this lead belongs.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * User assigned to handle this lead.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who registered/created this lead.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Opportunities created from this lead.
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Primary / latest opportunity converted from this lead.
     */
    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class)->latestOfMany();
    }

    /**
     * Site visits originated from this lead.
     */
    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }

    /**
     * Scope query by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by source.
     */
    public function scopeSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope query by assigned user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope query by customer.
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Configure Spatie Activity Logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leads')
            ->logOnly([
                'title',
                'customer_id',
                'source',
                'status',
                'estimated_value',
                'expected_start_date',
                'assigned_to',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.lead_model_created', ['title' => $this->title]),
                'updated' => __('activity.lead_model_updated', ['title' => $this->title]),
                'deleted' => __('activity.lead_model_deleted', ['title' => $this->title]),
                default => __('activity.lead_event', ['event' => $eventName, 'title' => $this->title]),
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
