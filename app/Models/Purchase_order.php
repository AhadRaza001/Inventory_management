<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase_order extends Model
{
    protected $fillable = [
        'po_no',
        'user_id',
        'supplier_id',
        'store_id',
        'status',
        'customer_requisitions',
        'customer_reference',
        'sub_total',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'due_amount'
    ];
    public function item_ledger()
    {
        return $this->morphMany(Item_ledger::class, 'reference', 'reference_type', 'reference_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function po_detail()
    {
        return $this->hasMany(Po_detail::class);
    }
}
