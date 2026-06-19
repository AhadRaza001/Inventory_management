<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packing_slip extends Model
{
       protected $fillable = [
        'sale_order_id',
        'store_id',
        'user_id',
        'ps_no',
        'status',
        'vehicle_no',
        'driver_name',
        'driver_phone',
        'remarks',
        'dispatch_date',
    ];
    
    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class);
    }
 
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function packing_slip_details()
    {
        return $this->hasMany(Packing_slip_detail::class);
    }
}
