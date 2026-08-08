<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Persuratan
            ['name' => 'akses_surat_masuk',        'label' => 'Surat Masuk',           'group' => 'Persuratan'],
            ['name' => 'akses_surat_keluar',       'label' => 'Surat Keluar',          'group' => 'Persuratan'],

            // Perjalanan Dinas
            ['name' => 'akses_skpd',               'label' => 'SKPD',                  'group' => 'Perjalanan Dinas'],

            // Tugas & Disposisi
            ['name' => 'akses_disposisi',          'label' => 'Disposisi Saya',         'group' => 'Tugas & Disposisi'],
            ['name' => 'akses_monitoring',         'label' => 'Monitoring Disposisi',   'group' => 'Tugas & Disposisi'],

            // Laporan
            ['name' => 'akses_laporan_surat_masuk',  'label' => 'Lap. Surat Masuk',    'group' => 'Laporan'],
            ['name' => 'akses_laporan_surat_keluar', 'label' => 'Lap. Surat Keluar',   'group' => 'Laporan'],
            ['name' => 'akses_laporan_disposisi',    'label' => 'Lap. Disposisi',       'group' => 'Laporan'],
            ['name' => 'akses_laporan_skpd',         'label' => 'Lap. SKPD',           'group' => 'Laporan'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }
    }
}
