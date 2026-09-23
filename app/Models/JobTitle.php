<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JobTitle extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'job_title_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'name_ar', 'name_en', 'code', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.job_title_created', ['name' => $this->name_ar ?: $this->name]),
                'updated' => __('activity.job_title_updated', ['name' => $this->name_ar ?: $this->name]),
                'deleted' => __('activity.job_title_deleted', ['name' => $this->name_ar ?: $this->name]),
                default => __('activity.job_title_event', ['event' => $eventName, 'name' => $this->name_ar ?: $this->name]),
            });
    }
}
