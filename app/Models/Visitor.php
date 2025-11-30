<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'organization',
        'image_url',
        'is_blacklisted',
        'reasons_for_blacklisting',
        'date_blacklisted',
        'blacklist_request_status',
        'blacklist_request_reason',
        'blacklist_requested_by',
        'blacklist_requested_at',
    ];

    public function blacklistRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blacklist_requested_by');
    }

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'date_blacklisted' => 'datetime',
    ];

    public function getImageUrlAttribute($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        // If already a full URL, return as-is
        if (is_string($value) && str_starts_with($value, 'http')) {
            return $value;
        }

        // Resolve URL from configured disk, with public fallback
        try {
            $isCloud = filled(config('cloudinary.cloud_url'))
                || (filled(config('cloudinary.cloud.cloud_name'))
                    && filled(config('cloudinary.cloud.api_key'))
                    && filled(config('cloudinary.cloud.api_secret')));
            $preferredDisk = $isCloud ? 'cloudinary' : 'public';
            $url = \Illuminate\Support\Facades\Storage::disk($preferredDisk)->url($value);
            if ($url) {
                return $url;
            }
        } catch (\Throwable $e) {
            // ignore and try public below
        }

        try {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($value);
        } catch (\Throwable $e) {
            return $value; // last resort: return raw path
        }
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (auth()->check() && empty($model->created_by_user_id)) {
                $model->created_by_user_id = auth()->id();
            }
            if (auth()->check() && empty($model->updated_by_user_id)) {
                $model->updated_by_user_id = auth()->id();
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by_user_id = auth()->id();
            }
        });
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
