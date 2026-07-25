<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skpd extends Model
{
    protected $fillable = [
        'user_id',
        'nomor_skpd',
        'nama_pegawai',
        'nip',
        'tujuan_dinas',
        'keperluan',
        'tanggal_berangkat',
        'tanggal_kembali',
        'durasi_hari',
        'biaya_transport',
        'biaya_penginapan',
        'biaya_konsumsi_per_hari',
        'total_biaya',
        'file',
        'status',
        'catatan_revisi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
