<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasCompany, BelongsToCompany;
    protected $fillable = ['company_id','name','symbol'];
    public function item(){
        return $this->hasMany(Item::class);
    }
}
