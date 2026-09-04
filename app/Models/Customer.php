<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasCompany, BelongsToCompany;
    protected $fillable = ['company_id','customer_no','name','phone','email','city','address','credit_limit','status'];
    public function sale_order()
    {
        return $this->hasMany(Sale_order::class);
    }
    public function invoice()
    {
        return $this->hasMany(Invoice::class);
    }
    public function payment()
    {
        return $this->hasMany(Payment::class);
    }
    public function customer_ledger()
    {
        return $this->hasMany(Customer_ledger::class);
    }   
}
