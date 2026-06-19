<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Po_detail extends Model
{
    protected $fillable = ['purchase_order_id','item_id','quantity','unit_cost','discount_amount','tax_amount','status','user_id'];
    public function purchase_order(){
        return $this->belongsTo(Purchase_order::class);
    }
    public function item(){
        return $this->belongsTo(Item::class);
    }
     public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
