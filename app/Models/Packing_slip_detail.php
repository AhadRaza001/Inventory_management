<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packing_slip_detail extends Model
{
    protected $fillable = [
        'packing_slip_id',
        'so_detail_id',
        'item_id',
        'ordered_qty',
        'packed_qty',
    ];


    public function packing_slip()
    {
        return $this->belongsTo(Packing_slip::class);
    }

    public function so_detail()
    {
        return $this->belongsTo(So_detail::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
