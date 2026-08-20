<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone', 'is_active', 'start_time', 'end_time', 'work_start', 'work_end', 'negocio_id'])]
class Employee extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Negocio, $this>
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Alias virtual "work_start" sobre la columna "start_time".
     */
    protected function getWorkStartAttribute(): ?string
    {
        return $this->attributes['start_time'] ?? null;
    }

    /**
     * Alias virtual "work_start" sobre la columna "start_time".
     */
    protected function setWorkStartAttribute(string $value): void
    {
        $this->attributes['start_time'] = $value;
    }

    /**
     * Alias virtual "work_end" sobre la columna "end_time".
     */
    protected function getWorkEndAttribute(): ?string
    {
        return $this->attributes['end_time'] ?? null;
    }

    /**
     * Alias virtual "work_end" sobre la columna "end_time".
     */
    protected function setWorkEndAttribute(string $value): void
    {
        $this->attributes['end_time'] = $value;
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
