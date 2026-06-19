<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sale_order()
    {
        return $this->hasMany(Sale_order::class);
    }
    public function purchase_order()
    {
        return $this->hasMany(Purchase_order::class);
    }
    public function po_detail()
    {
        return $this->hasMany(Po_detail::class);
    }
    public function so_detail()
    {
        return $this->hasMany(So_detail::class);
    }
    public function payment()
    {
        return $this->hasMany(Payment::class);
    }
    public function invoice()
    {
        return $this->hasMany(Invoice::class);
    }
}
