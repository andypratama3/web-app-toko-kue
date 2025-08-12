<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // ROUTE DI API
    // Jadi ini cukup kalau nama tabelnya sesuai
    protected $table = 'products';

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'name',
        'description',
        'price',
        'image'
    ];
}
