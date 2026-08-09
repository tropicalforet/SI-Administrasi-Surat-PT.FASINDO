<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class SuratMasuk extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nomor_surat',
        'kategori_surat',
        'sifat',
        'jalur_penerimaan',
        'tanggal_surat',
        'pengirim',
        'penerima_id',
        'penerima_role',
        'penerima',
        'perihal',
        'file',
        'status',
    ];

    /**
     * Status surat masuk beserta labelnya. Satu-satunya kosakata yang
     * dipakai controller, daftar, maupun laporan.
     */
    public const STATUS = [
        'baru'           => 'Baru',
        'didisposisikan' => 'Didisposisikan',
        'selesai'        => 'Selesai',
    ];

    /**
     * Tingkat kepentingan surat, menentukan prioritas penanganan.
     */
    public const SIFAT = [
        'biasa'   => 'Biasa',
        'penting' => 'Penting',
        'segera'  => 'Segera',
    ];

    /**
     * Lewat mana surat sampai ke kantor.
     */
    public const JALUR_PENERIMAAN = [
        'kurir'    => 'Kurir / Ekspedisi',
        'pos'      => 'Pos',
        'langsung' => 'Diantar Langsung',
        'email'    => 'Email',
        'whatsapp' => 'WhatsApp',
    ];

    /**
     * Sebelum SoftDeletes berlaku, foreign key cascadeOnDelete ikut menghapus
     * disposisi saat suratnya dihapus. Soft delete tidak memicu cascade itu,
     * sehingga disposisi tertinggal menunjuk surat yang sudah tersembunyi.
     * Penghapusan dan pemulihan dirambatkan di sini agar keduanya sejalan.
     */
    protected static function booted(): void
    {
        static::deleted(function (SuratMasuk $surat) {
            if (!$surat->isForceDeleting()) {
                $surat->disposisis()->update([
                    'deleted_at'            => $surat->deleted_at,
                    'dihapus_bersama_surat' => true,
                ]);
            }
        });

        static::restoring(function (SuratMasuk $surat) {
            // Hanya disposisi yang terhapus bersama suratnya yang dipulihkan;
            // yang dibatalkan tersendiri tetap berada di arsip.
            $surat->disposisis()
                ->onlyTrashed()
                ->where('dihapus_bersama_surat', true)
                ->update([
                    'deleted_at'            => null,
                    'dihapus_bersama_surat' => false,
                ]);
        });
    }

    public function disposisis()
    {
        return $this->hasMany(Disposisi::class);
    }

    /**
     * Hitung ulang status surat dari disposisinya.
     *
     * Belum ada disposisi berarti surat baru diagendakan; ada disposisi yang
     * masih berjalan berarti sedang ditindaklanjuti; seluruhnya selesai
     * berarti surat tuntas dan siap diarsipkan.
     */
    public function segarkanStatus(): void
    {
        if (!$this->disposisis()->exists()) {
            $status = 'baru';
        } elseif ($this->disposisis()->where('status', '!=', 'selesai')->exists()) {
            $status = 'didisposisikan';
        } else {
            $status = 'selesai';
        }

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst($this->status);
    }

    public function getLabelSifatAttribute(): string
    {
        return self::SIFAT[$this->sifat] ?? ucfirst((string) $this->sifat);
    }

    public function getLabelJalurAttribute(): string
    {
        return self::JALUR_PENERIMAAN[$this->jalur_penerimaan] ?? '-';
    }

    public function penerimaUser()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    /**
     * Pengguna yang berhak membaca surat ini karena ditujukan kepadanya,
     * baik secara perorangan maupun lewat role.
     */
    public function penerimaUsers()
    {
        if ($this->penerima_role) {
            return User::where('role', $this->penerima_role)->get();
        }

        return $this->penerimaUser ? collect([$this->penerimaUser]) : collect();
    }

    public function getLabelPenerimaAttribute(): string
    {
        if ($this->penerima_role) {
            return User::ROLE_PENERIMA_SURAT[$this->penerima_role] ?? ucfirst($this->penerima_role);
        }

        return $this->penerimaUser
            ? $this->penerimaUser->name . ' (' . ucfirst($this->penerimaUser->role) . ')'
            : ($this->penerima ?? '-');
    }

    /**
     * Batasi daftar surat pada yang boleh dibaca pengguna: ditujukan
     * langsung kepadanya, ditujukan ke rolenya, atau didisposisikan kepadanya.
     */
    /**
     * Versi tunggal dari scopeDapatDibacaOleh, dipakai halaman detail.
     * Aturannya sengaja dijaga sama persis dengan filter daftar agar tidak
     * ada surat yang tampil di daftar tapi ditolak saat dibuka.
     */
    public function bolehDibacaOleh(User $user): bool
    {
        if (in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])) {
            return true;
        }

        if ($this->penerima_id === $user->id) {
            return true;
        }

        if ($this->penerima_role && $this->penerima_role === strtolower($user->role)) {
            return true;
        }

        return $this->disposisis()->where('kepada_user_id', $user->id)->exists();
    }

    public function scopeDapatDibacaOleh($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('penerima_id', $user->id)
              ->orWhere('penerima_role', strtolower($user->role))
              ->orWhereHas('disposisis', function ($sq) use ($user) {
                  $sq->where('kepada_user_id', $user->id);
              });
        });
    }
}