<?php

namespace App\Scopes;

use App\Support\CurrentNegocio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NegocioScope implements Scope
{
    /**
     * Filter tenant models by the active negocio.
     * No-op when there is no active negocio (backwards compatible).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (CurrentNegocio::isSet()) {
            $builder->where($model->qualifyColumn('negocio_id'), CurrentNegocio::get());
        }
    }
}
