<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['name','description','address','phone'];
     public function item(){
        return $this->hasMany(Item::class);
    }
    public function item_ledger(){
        return $this->hasMany(Item_ledger::class);
    }
    public function sale_order(){
        return $this->hasMany(Sale_order::class);
    }
    public function purchase_order(){
        return $this->hasMany(Purchase_order::class);
    }
    public function so_detail(){
        return $this->hasMany(So_detail::class);
    }
}
