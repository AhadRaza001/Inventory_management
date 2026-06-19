<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale_order extends Model
{
    protected $fillable = ['so_no','user_id','customer_id','store_id','status','amount_status','customer_requisitions','customer_reference'];
    public function item_ledger()
    {
        return $this->morphMany(Item_ledger::class, 'reference', 'reference_type', 'reference_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function so_detail(){
        return $this->hasMany(So_detail::class);
    }
}
