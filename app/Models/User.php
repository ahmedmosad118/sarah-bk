<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, InteractsWithMedia;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'job_title_id',
        'status',
        'joining_date',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'joining_date' => 'date',
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');
        if ($media) {
            return $media->getUrl();
        }

        if (!empty($this->avatar)) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'data:')) {
                if (str_contains($this->avatar, 'storage/tenants/')) {
                    $relativePath = substr($this->avatar, strpos($this->avatar, 'storage/tenants/'));
                    return asset($relativePath);
                }
                return $this->avatar;
            }
            return asset(ltrim($this->avatar, '/'));
        }

        // Beautiful SVG avatar with user initials in Arabic/English with #00C896 and #0C1315
        $initial = mb_substr(trim($this->name ?: 'U'), 0, 1, 'UTF-8');
        return "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128"><rect width="128" height="128" rx="36" fill="#0C1315"/><rect x="4" y="4" width="120" height="120" rx="32" fill="none" stroke="#00C896" stroke-width="3" stroke-opacity="0.5"/><text x="50%" y="54%" font-family="Cairo, Outfit, sans-serif" font-weight="900" font-size="54" fill="#00C896" dominant-baseline="middle" text-anchor="middle">' . $initial . '</text></svg>');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'job_title_id', 'status', 'joining_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.user_created', ['name' => $this->name]),
                'updated' => __('activity.user_updated', ['name' => $this->name]),
                'deleted' => __('activity.user_deleted', ['name' => $this->name]),
                default => __('activity.user_event', ['event' => $eventName, 'name' => $this->name]),
            });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * Leads assigned to this user.
     */
    public function assignedLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Lead::class, 'assigned_to');
    }

    /**
     * Leads created by this user.
     */
    public function createdLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Lead::class, 'created_by');
    }
}
