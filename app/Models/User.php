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
        if ($this->avatar) {
            return $this->avatar;
        }

        $media = $this->getFirstMediaUrl('avatar');
        if ($media) {
            return $media;
        }

        $name = urlencode($this->name ?: 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=00C896&color=0C1315&size=128&bold=true";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'job_title_id', 'status', 'joining_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء المستخدم ' . $this->name,
                'updated' => 'تم تحديث بيانات المستخدم ' . $this->name,
                'deleted' => 'تم حذف المستخدم ' . $this->name,
                default => "إجراء {$eventName} على المستخدم " . $this->name,
            });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }
}
