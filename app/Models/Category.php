<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name','description'];
    public function item(){
        return $this->hasmany(item::class);
    }
}
