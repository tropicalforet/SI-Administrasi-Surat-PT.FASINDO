<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    protected $fillable = [
        'surat_masuk_id',
        'dari_user_id',
        'kepada_user_id',
        'instruksi',
        'catatan_tindak_lanjut',
        'file_tindak_lanjut',
        'status',
        'tanggal_disposisi',
        'parent_disposisi_id',
        'batas_waktu',
    ];

    protected $casts = [
        'batas_waktu' => 'date',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class);
    }

    public function dariUser()
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepadaUser()
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }

    public function parent()
{
    return $this->belongsTo(
        Disposisi::class,
        'parent_disposisi_id'
    );
}

public function children()
{
    return $this->hasMany(
        Disposisi::class,
        'parent_disposisi_id'
    );
}
}