<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'logo',
    ];

public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(Purchase_order::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(Sale_order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function itemLedgers()
    {
        return $this->hasMany(Item_ledger::class);
    }

    public function customerLedgers()
    {
        return $this->hasMany(Customer_ledger::class);
    }
     public function packingSlips()
    {
        return $this->hasMany(Packing_slip::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(Sale_return::class);
    }
}
