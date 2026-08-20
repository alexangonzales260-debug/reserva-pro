<?php

namespace App\Models\Concerns;

use App\Models\Negocio;
use App\Scopes\NegocioScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToNegocio
{
    /**
     * Register the tenant scope when the model boots.
     */
    public static function bootBelongsToNegocio(): void
    {
        static::addGlobalScope(new NegocioScope);
    }

    /**
     * @return BelongsTo<Negocio, $this>
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
