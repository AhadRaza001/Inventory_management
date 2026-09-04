<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasCompany, BelongsToCompany;
      public function customer(){
        return $this->belongsTo(Customer::class);
    }
    public function invoice_detail(){
        return $this->hasMany(Invoice_detail::class);
    }
    public function payment(){
        return $this->hasMany(Payment::class);
    }
    public function customer_ledger(){
        return $this->hasMany(Customer_ledger::class);
    }
     public function created_by()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
