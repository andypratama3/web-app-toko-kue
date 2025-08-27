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
        'status',
        'paid_at',
        'picked_up_at',
        'delivered_at',
        'received_by_buyer_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paid_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_by_buyer_at' => 'datetime',
        'created_at' => 'datetime', 
        'updated_at' => 'datetime',
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

    // Relasi ke User (pembuat pesanan)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Relasi ke Region
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
