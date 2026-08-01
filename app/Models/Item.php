<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{

    protected $fillable = ['category_id', 'unit_id', 'sku', 'name', 'description', 'purchase_price', 'sale_price', 'status', 'barcode', 'reorder_level'];
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function so_detail()
    {
        return $this->hasMany(So_detail::class);
    }
    public function po_detail()
    {
        return $this->hasMany(Po_detail::class);
    }
    public function item_ledger()
    {
        return $this->hasMany(Item_ledger::class);
    }
    public function invoice_detail()
    {
        return $this->hasMany(Invoice_detail::class);
    }
    public function packing_slip_details()
    {
        return $this->hasMany(Packing_slip_detail::class, 'item_id');
    }
}
