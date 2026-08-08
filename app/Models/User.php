<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
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