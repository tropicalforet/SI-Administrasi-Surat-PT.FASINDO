<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratMasuk extends Model
{
    protected $fillable = [
        'nomor_surat',
        'kategori_surat',
        'tanggal_surat',
        'pengirim',
        'penerima',
        'perihal',
        'file',
        'status',
    ];

    public function disposisis()
    {
        return $this->hasMany(Disposisi::class);
    }
}