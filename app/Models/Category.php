<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasCompany, BelongsToCompany;
    protected $fillable = ['company_id','name','description'];
    public function item(){
        return $this->hasmany(item::class);
    }
}
