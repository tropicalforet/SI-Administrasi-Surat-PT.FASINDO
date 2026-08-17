<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

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
     * Laporan Disposisi adalah alat kontrol manajemen, bukan alat kerja
     * harian: isinya rekam jejak siapa memerintah siapa dan mana yang
     * tertunda. Hanya Direktur Utama dan Sekretaris yang membacanya.
     *
     * Administrator ikut termasuk karena ia memegang seluruh sistem, sama
     * seperti pada modul lain - bukan sebagai bagian dari manajemen.
     *
     * Aturannya berbasis jabatan, bukan izin per pengguna, karena itulah
     * sifatnya: bukan sesuatu yang layak diberikan kepada seorang Direktur
     * bidang lewat satu centang di menu Manajemen User.
     */
    public static function bolehLihatLaporan(User $user): bool
    {
        return in_array(strtolower($user->role), self::ROLE_LAPORAN);
    }

    public const ROLE_LAPORAN = ['dirut', 'sekretaris', 'admin', 'administrator', 'superadmin'];

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

    /**
     * Nama pihak disposisi untuk ditampilkan.
     *
     * Pegawai yang sudah dihapus melepas penunjuknya menjadi kosong, tetapi
     * disposisinya sendiri tetap tersimpan sebagai arsip. Label ini menjaga
     * halaman tetap terbaca dan jujur menyebut bahwa orangnya tidak ada lagi.
     */
    public function getLabelPengirimAttribute(): string
    {
        return $this->dariUser?->name ?: 'Pengguna dihapus';
    }

    public function getLabelPenerimaAttribute(): string
    {
        return $this->kepadaUser?->name ?: 'Pengguna dihapus';
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