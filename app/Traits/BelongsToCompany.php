<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            // dd([
            //     'auth_check' => Auth::check(),
            //     'user_id' => Auth::id(),
            //     'company_id_before' => $model->company_id,
            //     'is_empty' => empty($model->company_id),
            // ]);
            if (Auth::check() && empty($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    Auth::user()->company_id
                );
            }
        });
    }
}
