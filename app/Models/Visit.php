<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'staff_visited_id',
        'reason_for_visit_id',
        'tag_number',
        'status',
        'checkin_time',
        'checkout_time',
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
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

        static::created(function (self $model) {
            // Send notification to staff member when visit is created
            if ($model->staff_visited_id && $model->staff) {
                $model->staff->notify(new \App\Notifications\VisitCreatedNotification($model));
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by_user_id = auth()->id();
            }
        });
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_visited_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(ReasonForVisit::class, 'reason_for_visit_id');
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
