<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Kategori as EnumKategori;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemTransaksi;
use App\Models\Produk;

final class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = ['kode', 'nama', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /** Menjembatani baris tabel dengan enum domain dari Modul 3. */
    public function enum(): EnumKategori
    {
        return EnumKategori::from($this->kode);
    }

    public function produk()
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function itemTerjual()
    {
        return $this->hasManyThrough(
            ItemTransaksi::class,
            Produk::class,
            'kategori_id',
            'produk_id',
            'id',
            'id'
        );
    }

    public function scopeAktif($query)
    {
        return $query->where('kategori.aktif', true);
    }
}
