<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Role yang dikenal sistem. Dipakai sebagai daftar tertutup saat
     * menyimpan pengguna agar role sembarangan tidak bisa masuk.
     */
    public const ROLES = [
        'admin',
        'administrator',
        'superadmin',
        'sekretaris',
        'dirut',
        'direktur1',
        'direktur2',
        'manager',
        'staff',
    ];

    /**
     * Label jabatan struktural untuk setiap role, dipakai seragam di
     * seluruh halaman agar tidak ada dua nama untuk role yang sama.
     */
    public const LABEL_ROLE = [
        'administrator' => 'Administrator Sistem',
        'admin'         => 'Administrator Sistem',
        'superadmin'    => 'Administrator Sistem',
        'dirut'         => 'Direktur Utama',
        'sekretaris'    => 'Sekretaris',
        'direktur1'     => 'Direktur Keuangan dan Administrasi',
        'direktur2'     => 'Direktur Teknik',
        'manager'       => 'Manager',
        'staff'         => 'Pelaksana / Admin',
    ];

    /**
     * Unit kerja sesuai bagan organisasi. Menentukan cabang mana seorang
     * pengguna berada, terpisah dari level wewenangnya.
     */
    public const UNIT = [
        'pimpinan'              => 'Pimpinan',
        'keuangan_administrasi' => 'Keuangan dan Administrasi',
        'teknik'                => 'Teknik',
    ];

    /**
     * Role yang wajib punya unit kerja. Dirut dan sekretaris berada di
     * pimpinan, administrator di luar bagan organisasi.
     */
    public const ROLE_WAJIB_UNIT = ['direktur1', 'direktur2', 'manager', 'staff'];

    /**
     * Role yang dapat dijadikan tujuan surat masuk, beserta labelnya.
     * Role administrator tidak termasuk karena bukan pelaksana persuratan.
     */
    public const ROLE_PENERIMA_SURAT = [
        'dirut'      => 'Direktur Utama',
        'direktur1'  => 'Direktur Keuangan dan Administrasi',
        'direktur2'  => 'Direktur Teknik',
        'sekretaris' => 'Sekretaris',
        'manager'    => 'Manager',
        'staff'      => 'Pelaksana / Admin',
    ];

    protected $fillable = [
        'name',
        'email',
        'role',
        'unit',
        'jabatan',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==========================
    // RELASI DISPOSISI
    // ==========================

    public function disposisiDibuat()
    {
        return $this->hasMany(Disposisi::class, 'dari_user_id');
    }

    public function disposisiDiterima()
    {
        return $this->hasMany(Disposisi::class, 'kepada_user_id');
    }

    // ==========================
    // RELASI PERMISSION (RBAC)
    // ==========================

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // ==========================
    // STRUKTUR ORGANISASI
    // ==========================

    /**
     * Nama jabatan untuk ditampilkan. Mengutamakan jabatan yang diisi
     * administrator, jatuh ke label role bila belum ditetapkan.
     */
    public function getLabelJabatanAttribute(): string
    {
        return $this->jabatan
            ?: (self::LABEL_ROLE[strtolower($this->role)] ?? ucfirst($this->role));
    }

    public function getLabelUnitAttribute(): string
    {
        return self::UNIT[$this->unit] ?? 'Belum ditetapkan';
    }

    public function isDirektur(): bool
    {
        return in_array(strtolower($this->role), ['direktur1', 'direktur2']);
    }

    /**
     * Role yang berada di bawah komando seorang direktur maupun manager.
     * Manager memimpin pelaksana, direktur memimpin keduanya.
     */
    public function rolesBawahan(): array
    {
        return match (strtolower($this->role)) {
            'direktur1', 'direktur2' => ['manager', 'staff'],
            'manager'                => ['staff'],
            default                  => [],
        };
    }

    /**
     * Bawahan dalam satu unit kerja. Inilah yang membuat disposisi tidak
     * dapat menyeberang garis komando: Direktur Teknik hanya melihat orang
     * di unit teknik, bukan seluruh pegawai berrole pelaksana.
     */
    public function bawahanSeunit()
    {
        $roles = $this->rolesBawahan();

        if (empty($roles) || !$this->unit) {
            return self::whereRaw('1 = 0')->get();
        }

        // Urut berdasarkan role lalu nama. 'manager' mendahului 'staff' secara
        // alfabetis, jadi tidak perlu ekspresi khusus yang mengikat ke MySQL.
        return self::whereIn('role', $roles)
            ->where('unit', $this->unit)
            ->orderBy('role')
            ->orderBy('name')
            ->get();
    }

    /**
     * Cek apakah user memiliki permission tertentu.
     * Role admin/dirut/sekretaris otomatis punya semua akses.
     */
    public function hasPermission(string $permissionName): bool
    {
        $bypassRoles = ['admin', 'administrator', 'superadmin', 'dirut', 'sekretaris'];

        if (in_array(strtolower($this->role), $bypassRoles)) {
            return true;
        }

        return $this->permissions()->where('name', $permissionName)->exists();
    }
}