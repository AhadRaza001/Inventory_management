<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    public function purchase_order(){
        return $this->hasMany(Purchase_order::class);
    }
}
