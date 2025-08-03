<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'note',
        'region_id', // <-- Tambahkan ini
    ];

    /**
     * Mendefinisikan relasi ke model Region.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
