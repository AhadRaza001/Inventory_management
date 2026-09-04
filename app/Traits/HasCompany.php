<?php

namespace App\Traits;

use App\Models\Company;

trait HasCompany
{
    /**
     * Get the company that owns the model.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
