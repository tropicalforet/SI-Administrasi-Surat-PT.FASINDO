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
        'catatan_verifikasi',
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
        'diverifikasi_pada'    => 'datetime',
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
     * Seluruh status disposisi beserta sebutannya.
     *
     * Daftar ini satu-satunya sumber kebenaran. Sebelumnya tiap tampilan
     * menuliskan sendiri rantai if-elseif untuk menerjemahkan status, sehingga
     * status yang baru ditambahkan tampil kosong di tampilan yang terlewat.
     */
    public const STATUS = [
        'menunggu'            => 'Menunggu',
        'diproses'            => 'Diproses',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'selesai'             => 'Selesai',
    ];

    /** Status yang boleh dipilih penerima saat mengisi tindak lanjut. */
    public const STATUS_PENERIMA = ['menunggu', 'diproses', 'menunggu_verifikasi'];

    /**
     * Status yang menandakan pekerjaan penerima sudah rampung.
     *
     * Dipakai pengingat tenggat: begitu penerima menyatakan rampung, giliran
     * berpindah ke pemberi disposisi, sehingga penerimanya tidak lagi pantas
     * ditegur terlambat atas keterlambatan yang bukan miliknya.
     */
    public const STATUS_RAMPUNG = ['selesai', 'menunggu_verifikasi'];

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Kelas warna lencana status, dipakai seluruh tampilan. */
    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status) {
            'selesai'             => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'menunggu_verifikasi' => 'bg-violet-50 text-violet-700 border-violet-200',
            'diproses'            => 'bg-blue-50 text-blue-700 border-blue-200',
            default               => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        };
    }

    public function sudahSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function menungguVerifikasi(): bool
    {
        return $this->status === 'menunggu_verifikasi';
    }

    /**
     * Penerima hanya mengerjakan selama disposisinya masih terbuka. Setelah
     * ia menyatakan rampung, giliran berpindah ke pemberi disposisi dan
     * pekerjaannya tidak lagi boleh diubah sepihak.
     */
    public function bolehDitindaklanjutiOleh(?User $user): bool
    {
        if (!$user || (int) $this->kepada_user_id !== (int) $user->id) {
            return false;
        }

        return !$this->sudahSelesai() && !$this->menungguVerifikasi();
    }

    /**
     * Yang memverifikasi adalah pihak yang memberi perintah. Ia pula yang
     * paling tahu apakah hasilnya sudah sesuai dengan yang diinstruksikan.
     */
    public function bolehDiverifikasiOleh(?User $user): bool
    {
        return $user
            && $this->menungguVerifikasi()
            && (int) $this->dari_user_id === (int) $user->id;
    }

    /**
     * Masih ada disposisi lanjutan yang belum selesai.
     *
     * Disposisi yang sedang menunggu verifikasi ikut terhitung belum selesai,
     * karena memang belum ditutup.
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