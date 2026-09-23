<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteVisit extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'customer_id',
        'opportunity_id',
        'lead_id',
        'status',
        'scheduled_date',
        'scheduled_time',
        'visit_date',
        'assigned_to',
        'general_assessment',
        'internal_notes',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date:Y-m-d',
        'visit_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Customer associated with this site visit.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Commercial Opportunity associated with this site visit (optional/nullable).
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Originating Lead for this site visit (if originated from lead).
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * User/Engineer assigned to perform the site visit.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who created the site visit record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Rooms/Spaces inspected and measured during this site visit.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(SiteVisitRoom::class);
    }

    /**
     * Scope query by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by customer.
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope query by opportunity.
     */
    public function scopeForOpportunity(Builder $query, int $opportunityId): Builder
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    /**
     * Scope query by assigned engineer/user.
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
                'customer_id',
                'opportunity_id',
                'lead_id',
                'status',
                'scheduled_date',
                'scheduled_time',
                'visit_date',
                'assigned_to',
                'general_assessment',
                'internal_notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.site_visit_created', ['id' => $this->id]),
                'updated' => __('activity.site_visit_updated', ['id' => $this->id]),
                'deleted' => __('activity.site_visit_deleted', ['id' => $this->id]),
                default => __('activity.site_visit_event', ['event' => $eventName, 'id' => $this->id]),
            });
    }

    /**
     * Register Spatie Media Library Collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('site_photos');
        $this->addMediaCollection('documents');
    }
}
