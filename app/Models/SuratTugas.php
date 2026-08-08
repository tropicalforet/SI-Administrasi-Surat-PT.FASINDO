<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratTugas extends Model
{
    use SoftDeletes;

    protected $table = 'surat_tugas';

    protected $fillable = [
        'nomor_surat_tugas',
        'user_id',
        'ditugaskan_oleh',
        'surat_masuk_id',
        'perihal_tugas',
        'tujuan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'file',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penugasOleh()
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function skpd()
    {
        return $this->hasOne(Skpd::class, 'surat_tugas_id');
    }
}
