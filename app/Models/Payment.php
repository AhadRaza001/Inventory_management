<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pest\ArchPresets\Custom;

class Payment extends Model
{
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function customer_ledger()
    {
        return $this->hasMany(Customer_ledger::class);
    }
    public function created_by()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
