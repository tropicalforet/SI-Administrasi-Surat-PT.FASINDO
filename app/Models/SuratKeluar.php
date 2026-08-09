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
        'draft'             => 'Draft',
        'menunggu_direktur' => 'Menunggu Verifikasi Direktur',
        'menunggu_dirut'    => 'Menunggu Persetujuan Dirut',
        'terkirim'          => 'Terkirim',
        'ditolak'           => 'Ditolak',
    ];

    protected $fillable = [
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

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getLabelUnitVerifikasiAttribute(): string
    {
        return User::UNIT[$this->unit_verifikasi] ?? '-';
    }

    /**
     * Direktur yang berwenang memverifikasi surat ini, yaitu direktur pada unit
     * yang dipilih saat surat disusun.
     */
    public function direkturVerifikator()
    {
        if (!$this->unit_verifikasi) {
            return null;
        }

        return User::whereIn('role', ['direktur1', 'direktur2'])
            ->where('unit', $this->unit_verifikasi)
            ->first();
    }

    public function getVerifyTokenAttribute()
    {
        $secret = config('app.key') ?: 'eoffice-secret-salt';
        $hash = substr(hash_hmac('sha256', "SuratKeluar-{$this->id}-{$this->created_at}", $secret), 0, 16);
        return "{$this->id}-{$hash}";
    }
}
