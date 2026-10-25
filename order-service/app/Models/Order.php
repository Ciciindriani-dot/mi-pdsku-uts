<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
    ];

    // Relasi: Satu order memiliki banyak order items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}