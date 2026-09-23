<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'customer_type',
        'name',
        'company_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'notes',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'display_name',
        'is_company',
    ];

    public function isCompany(): bool
    {
        return $this->customer_type === 'company';
    }

    public function isIndividual(): bool
    {
        return $this->customer_type === 'individual';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getIsCompanyAttribute(): bool
    {
        return $this->isCompany();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->isCompany() && !empty($this->company_name)) {
            return "{$this->company_name} ({$this->name})";
        }
        return $this->name;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompanies(Builder $query): Builder
    {
        return $query->where('customer_type', 'company');
    }

    public function scopeIndividuals(Builder $query): Builder
    {
        return $query->where('customer_type', 'individual');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customers')
            ->logOnly(['customer_type', 'name', 'company_name', 'phone', 'whatsapp', 'email', 'address', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.customer_created', ['name' => $this->name]),
                'updated' => __('activity.customer_updated', ['name' => $this->name]),
                'deleted' => __('activity.customer_deleted', ['name' => $this->name]),
                default => __('activity.customer_event', ['event' => $eventName, 'name' => $this->name]),
            });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

    /**
     * Leads associated with this customer.
     */
    public function leads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Lead::class);
    }

    /**
     * Opportunities associated with this customer.
     */
    public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Opportunity::class);
    }
}
