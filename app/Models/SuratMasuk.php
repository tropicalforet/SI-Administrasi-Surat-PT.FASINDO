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
        if ($this->tujuanJabatanSah()) {
            return User::where('role', $this->penerima_role)->get();
        }

        return $this->penerimaUser ? collect([$this->penerimaUser]) : collect();
    }

    /**
     * Tujuan berbasis jabatan hanya sah untuk jabatan tunggal.
     *
     * Surat lama sempat ditujukan ke "manager", padahal ada tiga manager di
     * unit berbeda - akibatnya surat unit Keuangan ikut terbaca manager unit
     * Teknik. Nilai lama itu dibiarkan tersimpan sebagai catatan tujuan asli,
     * tetapi tidak lagi memberi hak baca kepada siapa pun. Surat semacam itu
     * tetap dapat dibaca sekretaris, pimpinan, dan penerima disposisinya.
     */
    public function tujuanJabatanSah(): bool
    {
        return $this->penerima_role
            && array_key_exists($this->penerima_role, User::ROLE_PENERIMA_SURAT);
    }

    public function getLabelPenerimaAttribute(): string
    {
        if ($this->penerima_role) {
            return User::ROLE_PENERIMA_SURAT[$this->penerima_role] ?? ucfirst($this->penerima_role);
        }

        if (!$this->penerimaUser) {
            return $this->penerima ?? '-';
        }

        // Jabatan penuh, bukan kode role - "Staff" tidak menjelaskan apa pun
        // sementara "Pelaksana / Admin (Teknik)" langsung terbaca.
        $keterangan = $this->penerimaUser->label_jabatan;

        if ($this->penerimaUser->unit) {
            $keterangan .= ' - ' . $this->penerimaUser->label_unit;
        }

        return $this->penerimaUser->name . ' (' . $keterangan . ')';
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

        if ($this->tujuanJabatanSah() && $this->penerima_role === strtolower($user->role)) {
            return true;
        }

        return $this->disposisis()->where('kepada_user_id', $user->id)->exists();
    }

    /**
     * Jangkauan Laporan Surat Masuk.
     *
     * Laporan ini adalah buku agenda: isinya keterangan surat (tanggal, nomor,
     * pengirim, sifat, perihal, status), bukan isi maupun berkasnya. Jajaran
     * direksi - Dirut, Sekretaris, dan para Direktur bidang - membacanya
     * seluruhnya sebagai gambaran lalu lintas surat perusahaan.
     *
     * Selain mereka, yang tampil tetap hanya surat yang memang boleh dibaca
     * yang bersangkutan.
     */
    public function scopeLaporanUntuk($query, User $user)
    {
        $direksi = in_array(strtolower($user->role), ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'])
            || $user->isDirektur();

        return $direksi ? $query : $query->dapatDibacaOleh($user);
    }

    public function scopeDapatDibacaOleh($query, User $user)
    {
        $role = strtolower($user->role);

        return $query->where(function ($q) use ($user, $role) {
            $q->where('penerima_id', $user->id);

            // Hanya jabatan tunggal yang memberi hak baca lewat role.
            if (array_key_exists($role, User::ROLE_PENERIMA_SURAT)) {
                $q->orWhere('penerima_role', $role);
            }

            $q->orWhereHas('disposisis', function ($sq) use ($user) {
                $sq->where('kepada_user_id', $user->id);
            });
        });
    }
}