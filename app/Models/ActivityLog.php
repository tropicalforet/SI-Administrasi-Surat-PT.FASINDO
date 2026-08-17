<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [

        'user_id',

        'nama_pengguna',

        'aktivitas',

        'deskripsi'

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nama pelaku untuk ditampilkan. Mengutamakan pengguna yang masih
     * tercatat, lalu nama yang direkam saat kejadian bila ia sudah dihapus.
     */
    public function getLabelPelakuAttribute(): string
    {
        return $this->user?->name
            ?: ($this->nama_pengguna ? $this->nama_pengguna . ' (dihapus)' : 'Pengguna dihapus');
    }
}