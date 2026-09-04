<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Customer_ledger extends Model
{
    use HasCompany, BelongsToCompany;
    public function customer(){
        return $this->belongsTo(customer::class);
    }
     public function invoice(){
        return $this->belongsTo(Invoice::class);
    }
     public function payment(){
        return $this->belongsTo(payment::class);
    }
}
