<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

   protected $fillable = [
        'customer_id',
        'phone', // Tambahkan ke fillable
        'address', // Tambahkan ke fillable
        'payment_method',
        'note',
        'total_amount',
        'status',
        'courier_id', // Jika ada
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderDetail::class);
    }
}