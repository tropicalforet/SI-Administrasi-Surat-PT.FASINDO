<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skpd extends Model
{
    protected $fillable = [

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
    ];
}
