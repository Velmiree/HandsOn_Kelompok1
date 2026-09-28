<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Status struk. Sebelumnya berupa string bebas 'selesai' / 'batal'
 * yang tersebar di service. Dijadikan enum agar salah ketik menjadi
 * galat seketika, bukan bug diam-diam pada laporan.
 */
enum StatusTransaksi: string
{
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai',
            self::Batal => 'Dibatalkan',
        };
    }
}
