<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Kategori as EnumKategori;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function scopeAktif($query)
    {
        return $query->where('kategori.aktif', true);
    }
}
