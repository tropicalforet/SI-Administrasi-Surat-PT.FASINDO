<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class SuratTugas extends Model
{
    use SoftDeletes;

    protected $table = 'surat_tugas';

    /**
     * Dari mana perjalanan dinas ini berasal.
     */
    public const ASAL_USUL = [
        'penugasan' => 'Penugasan Atasan',
        'usulan'    => 'Usulan Pegawai',
    ];

    public const STATUS = [
        'draft'             => 'Draft',
        'menunggu_direktur' => 'Menunggu Persetujuan Direktur',
        'menunggu_dirut'    => 'Menunggu Penerbitan Dirut',
        'diterbitkan'       => 'Diterbitkan',
        'ditolak'           => 'Ditolak',
    ];

    protected $fillable = [
        'nomor_surat_tugas',
        'user_id',
        'ditugaskan_oleh',
        'asal_usul',
        'disetujui_direktur_by',
        'disetujui_direktur_at',
        'catatan_penolakan',
        'surat_masuk_id',
        'perihal_tugas',
        'tujuan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'file',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penugasOleh()
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function skpd()
    {
        return $this->hasOne(Skpd::class, 'surat_tugas_id');
    }

    public function disetujuiDirektur()
    {
        return $this->belongsTo(User::class, 'disetujui_direktur_by');
    }

    /**
     * Batasi daftar pada surat tugas yang boleh dilihat pengguna.
     *
     * Direktur wajib dapat melihat surat tugas pegawai di unitnya - tanpa ini
     * ia diberi kewajiban menyetujui usulan yang halamannya sendiri tidak
     * dapat ia buka.
     */
    public function scopeTerlihatOleh($query, User $user)
    {
        $role = strtolower($user->role);

        if (in_array($role, ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
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
        $role = strtolower($user->role);

        if (in_array($role, ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return true;
        }

        if ($this->user_id === $user->id || $this->ditugaskan_oleh === $user->id) {
            return true;
        }

        return $user->isDirektur()
            && $user->unit
            && $this->user?->unit === $user->unit;
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getLabelAsalUsulAttribute(): string
    {
        return self::ASAL_USUL[$this->asal_usul] ?? '-';
    }

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
     * unit bersangkutan adalah atasan langsungnya - keduanya tidak perlu
     * meminta persetujuan kepada siapa pun lagi.
     */
    public function perluPersetujuanDirektur(): bool
    {
        $penugas = $this->penugasOleh;

        if (!$penugas) {
            return true; // usulan pegawai sendiri
        }

        if (strtolower($penugas->role) === 'dirut') {
            return false;
        }

        return !($penugas->isDirektur() && $penugas->unit === $this->user?->unit);
    }
}
