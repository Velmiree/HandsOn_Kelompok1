<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Pemasok extends Model
{
    use HasFactory;

    protected $table = 'pemasok';

    protected $fillable = [
        'nama',
        'kota',
    ];

    public function produk(): BelongsToMany
    {
        return $this->belongsToMany(
            Produk::class,
            'pemasok_produk',
            'pemasok_id',
            'produk_id'
        )
            ->as('pasokan')
            ->withPivot('harga_beli', 'utama')
            ->withTimestamps();
    }
}