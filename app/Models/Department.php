<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'name',
    ];

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

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
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
