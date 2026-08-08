<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disposisi extends Model
{
    use SoftDeletes;

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
        'batas_waktu'          => 'date',
        'diingatkan_pada'      => 'datetime',
        'dieskalasi_pada'      => 'datetime',
        'siap_konfirmasi_pada' => 'datetime',
    ];

    /**
     * Masih ada disposisi lanjutan yang belum selesai.
     */
    public function punyaAnakBelumSelesai(): bool
    {
        return $this->children()->where('status', '!=', 'selesai')->exists();
    }

    /**
     * Punya disposisi lanjutan dan seluruhnya sudah selesai.
     */
    public function semuaAnakSelesai(): bool
    {
        return $this->children()->exists() && !$this->punyaAnakBelumSelesai();
    }

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