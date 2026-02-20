<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }

    /**
     * Manager location selection
     */
    public function setManagerActiveLocation(Location $location): void
    {
        if (!$this->locations()
            ->where('locations.id', $location->id)
            ->exists()) {
            throw new DomainException('LOCATION_NOT_ALLOWED');
        }

        $this->managerActiveLocation()->updateOrCreate(
            ['user_id' => $this->id],
            ['location_id' => $location->id]
        );
    }

    /**
     * Manager assignment // could be many location
     */
    public function attachManagerToLocation(Location $location): void
    {
        if ($this->locationAssignments()
            ->where('location_id', $location->id)
            ->whereNull('active_to')
            ->exists()
        ) {
            return;
        }

        $this->locationAssignments()->create([
            'location_id' => $location->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);
    }

    /**
     * Operator assignment
     */
    public function assignToLocation(Location $location): void
    {
        $current = $this->locationAssignments()
            ->whereNull('active_to')
            ->first();

        // If already assigned to this location, do nothing
        if ($current && $current->location_id === $location->id) {
            return;
        }

        // Close current assignment if exists
        if ($current) {
            $current->update([
                'active_to' => now(),
            ]);
        }

        // Create new assignment
        $this->locationAssignments()->create([
            'location_id' => $location->id,
            'active_from' => now(),
            'active_to'   => null,
        ]);
    }

    public function whomActAs(UserRole $role): bool
    {
        return $this->role?->code === $role->value;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function locationAssignments(): HasMany
    {
        return $this->hasMany(UserLocationAssignment::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            Location::class,
            'user_location_assignments',
            'user_id',
            'location_id'
        )
            ->withPivot(['active_from', 'active_to'])
            ->withTimestamps();
    }

    /**
     * Determine selected location by manager
     */
    public function managerActiveLocation(): HasOne
    {
        return $this->hasOne(ManagerActiveLocation::class);
    }

    /**
     * Check operator active location
     */
    public function activeLocation(): HasOne
    {
        return $this->hasOne(UserLocationAssignment::class)
            ->whereNull('active_to')
            ->latestOfMany();
    }
}
