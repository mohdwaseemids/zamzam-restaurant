<?php

namespace App\Models;

use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    /** @use HasFactory<RestaurantTableFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (blank($table->token)) {
                $table->token = Str::lower(Str::random(20));
            }
        });
    }

    /** The address printed as a QR code on the table. */
    public function getOrderUrlAttribute(): string
    {
        return route('table.scan', $this->token);
    }
}
