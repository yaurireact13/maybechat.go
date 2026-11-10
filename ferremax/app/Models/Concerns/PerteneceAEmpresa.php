<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait PerteneceAEmpresa
{
    protected static function bootPerteneceAEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder) {
            // El super admin no pertenece a ninguna empresa (empresa_id nulo):
            // no se filtra, de modo que pueda consultar datos de cualquier tenant.
            if (auth()->check() && ! is_null(auth()->user()->empresa_id)) {
                $builder->where($builder->getModel()->getTable() . '.empresa_id', auth()->user()->empresa_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && ! is_null(auth()->user()->empresa_id) && empty($model->empresa_id)) {
                $model->empresa_id = auth()->user()->empresa_id;
            }
        });
    }
}
