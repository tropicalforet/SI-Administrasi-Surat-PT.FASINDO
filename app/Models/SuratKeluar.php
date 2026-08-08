<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratKeluar extends Model
{
    use SoftDeletes;

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

    public function getVerifyTokenAttribute()
    {
        $secret = config('app.key') ?: 'eoffice-secret-salt';
        $hash = substr(hash_hmac('sha256', "SuratKeluar-{$this->id}-{$this->created_at}", $secret), 0, 16);
        return "{$this->id}-{$hash}";
    }
}
