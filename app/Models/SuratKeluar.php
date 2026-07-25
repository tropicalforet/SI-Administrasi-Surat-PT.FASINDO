<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    protected $fillable = [
        'nomor_surat',
        'kategori_surat',
        'tanggal_surat',
        'tujuan',
        'perihal',
        'file',
        'status',
        'approved_direktur_by',
        'approved_direktur_at',
        'approved_dirut_by',
        'approved_dirut_at',
        'catatan_revisi'
        
    ];

    public function approvedDirektur()
{
    return $this->belongsTo(
        User::class,
        'approved_direktur_by'
    );
}

public function approvedDirut()
{
    return $this->belongsTo(
        User::class,
        'approved_dirut_by'
    );
}
}
