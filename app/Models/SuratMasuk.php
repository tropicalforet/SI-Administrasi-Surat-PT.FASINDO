<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratMasuk extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nomor_surat',
        'kategori_surat',
        'tanggal_surat',
        'pengirim',
        'penerima_id',
        'penerima',
        'perihal',
        'file',
        'status',
    ];

    public function disposisis()
    {
        return $this->hasMany(Disposisi::class);
    }

    public function penerimaUser()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }
}