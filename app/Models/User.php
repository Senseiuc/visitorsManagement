<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'intercom',
        'assigned_location_id',
        'permissions',
        'created_by_user_id',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    // Roles relations
    public function roleRelation(): BelongsTo
    {
        // Primary/legacy single role (kept for backward compatibility)
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function roles(): BelongsToMany
    {
        // New multi-role relation
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    // Role helpers (based on any assigned role slug OR legacy roleRelation)
    public function isSuperAdmin(): bool
    {
        if (($this->roleRelation?->slug ?? null) === 'superadmin') {
            return true;
        }
        return $this->roles()->where('slug', 'superadmin')->exists();
    }

    public function isAdmin(): bool
    {
        if (($this->roleRelation?->slug ?? null) === 'admin') {
            return true;
        }
        return $this->roles()->where('slug', 'admin')->exists();
    }

    public function isReceptionist(): bool
    {
        if (($this->roleRelation?->slug ?? null) === 'receptionist') {
            return true;
        }
        return $this->roles()->where('slug', 'receptionist')->exists();
    }

    public function isStaff(): bool
    {
        if (($this->roleRelation?->slug ?? null) === 'staff') {
            return true;
        }
        return $this->roles()->where('slug', 'staff')->exists();
    }

    public function hasRole(string $slug): bool
    {
        $legacy = $this->roleRelation?->slug ?? null;
        if ($legacy === $slug) {
            return true;
        }
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function effectivePermissions(): array
    {
        $own = $this->permissions ?? [];

        // Gather permissions from legacy single role and all assigned roles
        $rolePerms = [];
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        foreach ($roles as $role) {
            $rolePerms = array_merge($rolePerms, $role->permissions ?? []);
        }

        // Also include legacy primary role permissions if present
        $rolePerms = array_merge($rolePerms, $this->roleRelation?->permissions ?? []);

        // Ensure unique
        return array_values(array_unique(array_merge($rolePerms, $own)));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }

    // Location Relationships
    public function assignedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'assigned_location_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user')->withTimestamps();
    }

    /**
     * Return array of accessible location IDs for this user.
     * Uses many-to-many pivot if set; falls back to assigned_location_id for backward compatibility.
     * Super Admin returns null to indicate all.
     *
     * @return array<int>|null
     */
    public function accessibleLocationIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null; // null means no restriction
        }

        $ids = $this->locations()->pluck('locations.id')->all();
        if (!empty($ids)) {
            return $ids;
        }

        if ($this->assigned_location_id) {
            return [(int) $this->assigned_location_id];
        }

        return [];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get this user's own location IDs (from pivot, fallback to assigned_location_id).
     *
     * @return array<int>
     */
    public function locationIds(): array
    {
        $ids = $this->locations()->pluck('locations.id')->all();
        if (!empty($ids)) {
            return array_map('intval', $ids);
        }
        return $this->assigned_location_id ? [(int) $this->assigned_location_id] : [];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
