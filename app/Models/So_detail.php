<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class So_detail extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'sale_order_id',
        'discount_amount',
        'tax_amount',
        'item_id',
        'quantity',
        'delivered_qty',
        'remaining_qty',
        'status',
        'delivered_now'
    ];
    public function sale_order()
    {
        return $this->belongsTo(Sale_order::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function created_by()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
