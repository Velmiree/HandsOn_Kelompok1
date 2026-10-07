<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Kategori as EnumKategori;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    /** Satu kategori memiliki banyak produk. */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    /**
     * Seluruh item transaksi dari produk-produk dalam kategori ini.
     *
     * Produk yang sudah soft-delete tetap dihitung dalam laporan.
     */
    public function itemTerjual(): HasManyThrough
    {
        return $this->hasManyThrough(
            ItemTransaksi::class,
            Produk::class,
            'kategori_id',
            'produk_id',
        )->withTrashedParents();
    }

    public function scopeAktif($query)
    {
        return $query->where('kategori.aktif', true);
    }
}