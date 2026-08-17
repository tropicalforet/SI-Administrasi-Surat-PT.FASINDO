<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skpd extends Model
{
    use SoftDeletes;

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

    /**
     * Kewenangan menugaskan mengikuti garis komando, bukan pangkat semata.
     *
     * Direktur Utama membawahi seluruh struktur. Direktur bidang hanya
     * membawahi tiga posisi di direktoratnya masing-masing. Sekretaris tidak
     * termasuk: ia tidak punya bawahan di bagan, jadi ia mengusulkan untuk
     * dirinya sendiri seperti pegawai lain.
     */
    public static function bolehMenugaskan(User $user): bool
    {
        return strtolower($user->role) === 'dirut' || $user->isDirektur();
    }

    /**
     * Pegawai yang boleh ditugaskan oleh $penugas.
     *
     * Administrator tidak muncul karena berada di luar bagan organisasi.
     * Penugas selalu termasuk dirinya sendiri - seorang direktur pun perlu
     * jalan untuk berdinas, dan dokumen atas namanya sendiri diperlakukan
     * sebagai usulan, bukan penugasan.
     */
    public static function calonPegawai(User $penugas)
    {
        if (strtolower($penugas->role) === 'dirut') {
            return User::whereNotIn('role', ['admin', 'administrator', 'superadmin'])
                ->orderBy('unit')->orderBy('role')->orderBy('name')->get();
        }

        if ($penugas->isDirektur()) {
            return $penugas->bawahanSeunit()->prepend($penugas);
        }

        return collect();
    }

    /**
     * Apakah $penugas berwenang menugaskan pegawai tertentu.
     */
    public static function bolehMenugaskanPegawai(User $penugas, $pegawaiId): bool
    {
        return self::calonPegawai($penugas)
            ->contains(fn ($calon) => (int) $calon->id === (int) $pegawaiId);
    }

    protected $fillable = [
        'user_id',
        'ditugaskan_oleh',
        'disetujui_direktur_by',
        'disetujui_direktur_at',
        'surat_masuk_id',
        'surat_tugas_id',
        'nomor_skpd',
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

    /**
     * Pengajuan belum bernomor sampai disetujui Direktur Utama.
     */
    public function getLabelNomorAttribute(): string
    {
        return $this->nomor_skpd ?: '(Belum bernomor)';
    }

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

    public function getLabelAsalUsulAttribute(): string
    {
        return self::ASAL_USUL[$this->asal_usul] ?? '-';
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
     * Perlu persetujuan direktur bila ada direktur di atas pegawainya yang
     * belum menilai dokumen ini.
     */
    public function perluPersetujuanDirektur(): bool
    {
        $direktur = $this->direkturPenyetuju();

        // Tidak ada direktur di atasnya - pegawai unit pimpinan, atau
        // pegawainya justru direktur itu sendiri. Tidak seorang pun boleh
        // menjadi penyetuju usulannya sendiri, jadi langsung ke Dirut.
        if (!$direktur || $direktur->id === $this->user?->id) {
            return false;
        }

        $penugas = $this->ditugaskanOleh;

        // Usulan pegawai selalu dinilai direkturnya lebih dulu.
        if (!$penugas) {
            return true;
        }

        // Dirut adalah keputusan terakhir; ia tidak meminta izin bawahannya.
        if (strtolower($penugas->role) === 'dirut') {
            return false;
        }

        // Direktur yang menugaskan bawahannya sendiri sudah menyatakan
        // persetujuannya lewat penugasan itu - tidak perlu menyetujui dua kali.
        return $penugas->id !== $direktur->id;
    }

    // ==========================
    // KETERLIHATAN
    // ==========================

    /**
     * Draf belum diajukan, jadi masih kertas kerja pemiliknya - baik pegawai
     * yang mengusulkan maupun atasan yang sedang menyusun penugasan. Tidak
     * seorang pun melihat draf orang lain, termasuk pimpinan dan
     * administrator. Dipakai bersama oleh daftar SKPD dan laporan.
     */
    public function scopeTanpaDrafOrangLain($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('status', '!=', 'draft')
              ->orWhere('user_id', $user->id)
              ->orWhere('ditugaskan_oleh', $user->id);
        });
    }

    /**
     * Jangkauan rekapitulasi pada Laporan SKPD, mengikuti garis komando.
     *
     * Direktur Utama dan Sekretaris merekap seluruh karyawan. Direktur bidang
     * merekap direktoratnya saja - bawahannya berikut dirinya sendiri, karena
     * rekap divisi yang menghilangkan perjalanan direkturnya bukan rekap yang
     * utuh. Selebihnya hanya melihat miliknya sendiri.
     */
    public function scopeLaporanUntuk($query, User $user)
    {
        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return $query;
        }

        if ($user->isDirektur() && $user->unit) {
            return $query->whereHas('user', fn ($q) => $q->where('unit', $user->unit));
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Direktur wajib dapat melihat penugasan pegawai di unitnya - tanpa ini
     * ia diberi kewajiban menyetujui dokumen yang tidak dapat ia buka.
     */
    public function scopeTerlihatOleh($query, User $user)
    {
        $query->tanpaDrafOrangLain($user);

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
        if ($this->user_id === $user->id || $this->ditugaskan_oleh === $user->id) {
            return true;
        }

        // Draf orang lain tertutup bagi siapa pun.
        if ($this->status === 'draft') {
            return false;
        }

        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
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
