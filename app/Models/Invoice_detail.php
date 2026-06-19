<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice_detail extends Model
{
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
