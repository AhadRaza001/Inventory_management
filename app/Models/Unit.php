<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['name','symbol'];
    public function item(){
        return $this->hasMany(Item::class);
    }
}
