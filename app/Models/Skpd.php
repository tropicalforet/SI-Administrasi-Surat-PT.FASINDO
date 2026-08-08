<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skpd extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'surat_tugas_id',
        'nomor_skpd',
        'nama_pegawai',
        'nip',
        'tujuan_dinas',
        'keperluan',
        'tanggal_berangkat',
        'tanggal_kembali',
        'durasi_hari',
        'file',
        'status',
        'catatan_revisi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function suratTugas()
    {
        return $this->belongsTo(SuratTugas::class, 'surat_tugas_id');
    }

    public function getVerifyTokenAttribute()
    {
        $secret = config('app.key') ?: 'eoffice-secret-salt';
        $hash = substr(hash_hmac('sha256', "SKPD-{$this->id}-{$this->created_at}", $secret), 0, 16);
        return "{$this->id}-{$hash}";
    }
}
