<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Item_ledger extends Model
{
    use HasCompany, BelongsToCompany;
    protected $fillable = [
        'item_id',
        'store_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'transaction_date',
        'created_by',
    ];
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}
