<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'region_id',
    ];

    /**
     * Mendefinisikan relasi ke model Region.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Secara otomatis mengubah atribut 'name' menjadi format Title Case
     * sebelum menyimpannya ke database.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            // `set` dieksekusi saat menyimpan data
            set: fn($value) => ucwords(strtolower($value)),
        );
    }

    /**
     * Secara otomatis mengubah atribut 'address' menjadi format Title Case
     * sebelum menyimpannya ke database.
     */
    protected function address(): Attribute
    {
        return Attribute::make(
            // `ucwords` akan membuat setiap awal kata menjadi kapital
            // `strtolower` untuk menormalkan input (misal: "JALAN" -> "jalan" -> "Jalan")
            set: fn($value) => ucwords(strtolower($value)),
        );
    }
}
