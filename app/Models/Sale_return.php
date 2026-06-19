<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale_return extends Model
{
     protected $fillable = [
        'sale_order_id',
        'packing_slip_id',
        'store_id',
        'user_id',
        'sr_no',
        'status',
        'return_type',
        'reason',
        'return_date',
    ];
 
    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
 
    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class);
    }
 
    public function packing_slip()
    {
        return $this->belongsTo(Packing_slip::class);
    }
 
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function sale_return_details()
    {
        return $this->hasMany(Sale_return_detail::class);
    }
}
