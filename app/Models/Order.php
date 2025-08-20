<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // PASTIKAN SEMUA KOLOM INI ADA DI DALAM $fillable
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'phone',
        'address',
        'total_amount',
        'payment_method',
        'payment_proof',
        'note',
        'created_by_user_id',
        'region_id',          
    ];

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}