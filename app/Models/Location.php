<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'uuid',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // Always generate UUID, even in seeders
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            
            // Set audit fields only if user is authenticated
            if (auth()->check()) {
                if (empty($model->created_by_user_id)) {
                    $model->created_by_user_id = auth()->id();
                }
                if (empty($model->updated_by_user_id)) {
                    $model->updated_by_user_id = auth()->id();
                }
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by_user_id = auth()->id();
            }
        });
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'location_user')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
