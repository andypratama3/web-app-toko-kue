<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'meta_phone_number_id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function findByPhoneNumberId(?string $phoneNumberId): ?self
    {
        if (!$phoneNumberId) {
            return null;
        }

        return static::where('meta_phone_number_id', $phoneNumberId)->first();
    }
}
