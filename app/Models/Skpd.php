<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skpd extends Model
{
    use SoftDeletes;

    /**
     * SKPD kini mencakup dua macam penugasan. Tujuan dan lama perjalanan
     * hanya relevan bagi yang benar-benar bepergian.
     */
    public const JENIS = [
        'perjalanan_dinas' => 'Perjalanan Dinas',
        'internal'         => 'Tugas Internal',
    ];

    /**
     * Dari mana penugasan ini berasal.
     */
    public const ASAL_USUL = [
        'penugasan' => 'Penugasan Atasan',
        'usulan'    => 'Usulan Pegawai',
    ];

    public const STATUS = [
        'draft'             => 'Draft',
        'menunggu_direktur' => 'Menunggu Persetujuan Direktur',
        'menunggu_dirut'    => 'Menunggu Persetujuan Dirut',
        'disetujui'         => 'Disetujui',
        'ditolak'           => 'Ditolak',
    ];

    protected $fillable = [
        'user_id',
        'ditugaskan_oleh',
        'disetujui_direktur_by',
        'disetujui_direktur_at',
        'surat_masuk_id',
        'surat_tugas_id',
        'nomor_skpd',
        'jenis',
        'asal_usul',
        'nama_pegawai',
        'tujuan_dinas',
        'keperluan',
        'tanggal_berangkat',
        'tanggal_kembali',
        'durasi_hari',
        'file',
        'status',
        'catatan_revisi',
    ];

    protected $casts = [
        'disetujui_direktur_at' => 'datetime',
    ];

    // ==========================
    // RELASI
    // ==========================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ditugaskanOleh()
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function disetujuiDirektur()
    {
        return $this->belongsTo(User::class, 'disetujui_direktur_by');
    }

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    /**
     * Peninggalan modul Surat Tugas yang sudah digabung ke sini. Dipertahankan
     * agar dokumen hasil konversi masih dapat ditelusuri ke asalnya.
     */
    public function suratTugas()
    {
        return $this->belongsTo(SuratTugas::class, 'surat_tugas_id');
    }

    // ==========================
    // LABEL
    // ==========================

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? '-';
    }

    public function getLabelAsalUsulAttribute(): string
    {
        return self::ASAL_USUL[$this->asal_usul] ?? '-';
    }

    public function berupaPerjalanan(): bool
    {
        return $this->jenis === 'perjalanan_dinas';
    }

    // ==========================
    // ALUR PERSETUJUAN
    // ==========================

    /**
     * Direktur yang berwenang atas pegawai yang ditugaskan.
     */
    public function direkturPenyetuju(): ?User
    {
        if (!$this->user?->unit) {
            return null;
        }

        return User::whereIn('role', ['direktur1', 'direktur2'])
            ->where('unit', $this->user->unit)
            ->first();
    }

    /**
     * Perlu persetujuan direktur bila penugasan tidak diterbitkan pihak yang
     * memang berwenang atas pegawainya. Dirut membawahi semua, dan direktur
     * unit bersangkutan adalah atasan langsungnya.
     */
    public function perluPersetujuanDirektur(): bool
    {
        $penugas = $this->ditugaskanOleh;

        if (!$penugas) {
            return true; // usulan pegawai sendiri
        }

        if (strtolower($penugas->role) === 'dirut') {
            return false;
        }

        return !($penugas->isDirektur() && $penugas->unit === $this->user?->unit);
    }

    // ==========================
    // KETERLIHATAN
    // ==========================

    /**
     * Direktur wajib dapat melihat penugasan pegawai di unitnya - tanpa ini
     * ia diberi kewajiban menyetujui dokumen yang tidak dapat ia buka.
     */
    public function scopeTerlihatOleh($query, User $user)
    {
        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('ditugaskan_oleh', $user->id);

            if ($user->isDirektur() && $user->unit) {
                $q->orWhereHas('user', fn ($sq) => $sq->where('unit', $user->unit));
            }
        });
    }

    public function dapatDilihatOleh(User $user): bool
    {
        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return true;
        }

        if ($this->user_id === $user->id || $this->ditugaskan_oleh === $user->id) {
            return true;
        }

        return $user->isDirektur()
            && $user->unit
            && $this->user?->unit === $user->unit;
    }

    public function getVerifyTokenAttribute()
    {
        $secret = config('app.key') ?: 'eoffice-secret-salt';
        $hash = substr(hash_hmac('sha256', "SKPD-{$this->id}-{$this->created_at}", $secret), 0, 16);
        return "{$this->id}-{$hash}";
    }
}
