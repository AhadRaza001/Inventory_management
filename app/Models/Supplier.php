<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasCompany, BelongsToCompany;
    protected $fillable = ['company_id','name','sku','phone','city','address','email','credit_limit'];
    public function purchase_order(){
        return $this->hasMany(Purchase_order::class);
    }
}
