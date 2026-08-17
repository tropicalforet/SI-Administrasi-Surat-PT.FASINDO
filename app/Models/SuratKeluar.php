<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class SuratKeluar extends Model
{
    use SoftDeletes;

    /**
     * Alur persetujuan surat keluar.
     */
    public const STATUS = [
        'draft'               => 'Draft',
        'menunggu_direktur'   => 'Menunggu Verifikasi Direktur (alur lama)',
        'menunggu_sekretaris' => 'Menunggu Penomoran Sekretaris',
        'menunggu_dirut'      => 'Menunggu Tanda Tangan Dirut',
        'terkirim'            => 'Terkirim',
        'ditolak'             => 'Ditolak',
    ];

    protected $fillable = [
        'dibuat_oleh',
        'nomor_surat',
        'kategori_surat',
        'unit_verifikasi',
        'tanggal_surat',
        'tujuan',
        'perihal',
        'file',
        'status',
        'approved_direktur_by',
        'approved_direktur_at',
        'approved_dirut_by',
        'approved_dirut_at',
        'catatan_revisi'
        
    ];

    public function approvedDirektur()
{
    return $this->belongsTo(
        User::class,
        'approved_direktur_by'
    );
}

    public function approvedDirut()
    {
        return $this->belongsTo(
            User::class,
            'approved_dirut_by'
        );
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Draf belum bernomor sampai sekretaris memprosesnya.
     */
    public function getLabelNomorAttribute(): string
    {
        return $this->nomor_surat ?: '(Belum bernomor)';
    }

    /**
     * Draf adalah kertas kerja penyusunnya, belum menjadi dokumen kantor
     * sampai diajukan. Karena itu tidak seorang pun melihat draf orang lain -
     * termasuk sekretaris, Direktur Utama, dan administrator. Begitu diajukan,
     * aturan keterlihatan biasa kembali berlaku.
     *
     * Ditulis sebagai scope tersendiri supaya daftar surat dan laporan
     * memakai batasan yang sama persis.
     */
    public function scopeTanpaDrafOrangLain($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('status', '!=', 'draft')
              ->orWhere('dibuat_oleh', $user->id);
        });
    }

    /**
     * Jangkauan Laporan Surat Keluar.
     *
     * Laporan ini adalah ikhtisar surat yang benar-benar diterbitkan, jadi
     * draf tidak masuk rekap siapa pun - termasuk draf milik pembaca laporan
     * itu sendiri. Jajaran direksi (Dirut, Sekretaris, dan para Direktur
     * bidang) membaca seluruh perusahaan; selebihnya hanya surat susunannya.
     */
    public function scopeLaporanUntuk($query, User $user)
    {
        $query->where('status', '!=', 'draft');

        $direksi = in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])
            || $user->isDirektur();

        return $direksi ? $query : $query->where('dibuat_oleh', $user->id);
    }

    /**
     * Batasi daftar pada surat yang boleh dilihat pengguna.
     *
     * Penyusun melihat surat buatannya sendiri, direktur melihat surat yang
     * menjadi kewenangan direktoratnya, sedangkan sekretaris dan pimpinan
     * melihat seluruhnya - kecuali draf orang lain.
     */
    public function scopeTerlihatOleh($query, User $user)
    {
        $query->tanpaDrafOrangLain($user);

        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('dibuat_oleh', $user->id);

            // Direktur melihat surat yang berasal dari unitnya, sebagai
            // keterbukaan - bukan karena punya tahap persetujuan.
            if ($user->isDirektur() && $user->unit) {
                $q->orWhere('unit_verifikasi', $user->unit);
            }
        });
    }

    public function dapatDilihatOleh(User $user): bool
    {
        if ($this->dibuat_oleh === $user->id) {
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
            && $this->unit_verifikasi === $user->unit;
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getLabelUnitVerifikasiAttribute(): string
    {
        return User::UNIT[$this->unit_verifikasi] ?? '-';
    }

    public function getVerifyTokenAttribute()
    {
        $secret = config('app.key') ?: 'eoffice-secret-salt';
        $hash = substr(hash_hmac('sha256', "SuratKeluar-{$this->id}-{$this->created_at}", $secret), 0, 16);
        return "{$this->id}-{$hash}";
    }
}
