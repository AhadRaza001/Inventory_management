<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale_return_detail extends Model
{
      protected $fillable = [
        'sale_return_id',
        'packing_slip_detail_id',
        'item_id',
        'dispatched_qty',
        'return_qty',
        'remarks',
    ];
 
    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
 
    public function sale_return()
    {
        return $this->belongsTo(Sale_return::class);
    }
 
    public function packing_slip_detail()
    {
        return $this->belongsTo(Packing_slip_detail::class);
    }
 
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
