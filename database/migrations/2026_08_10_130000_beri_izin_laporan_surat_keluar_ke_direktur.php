<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laporan Surat Keluar dibuka untuk jajaran direksi.
     *
     * Sama seperti Laporan Surat Masuk: Dirut dan Sekretaris masuk lewat
     * pintasan role di User::hasPermission, sedangkan Direktur bidang
     * bergantung pada izin yang ditetapkan per pengguna.
     */
    public function up(): void
    {
        $izin = DB::table('permissions')->where('name', 'akses_laporan_surat_keluar')->first();

        if (!$izin) {
            return;
        }

        $direktur = DB::table('users')->whereIn('role', ['direktur1', 'direktur2'])->pluck('id');

        foreach ($direktur as $userId) {
            $sudahPunya = DB::table('permission_user')
                ->where('user_id', $userId)
                ->where('permission_id', $izin->id)
                ->exists();

            if (!$sudahPunya) {
                DB::table('permission_user')->insert([
                    'user_id'       => $userId,
                    'permission_id' => $izin->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $izin = DB::table('permissions')->where('name', 'akses_laporan_surat_keluar')->first();

        if (!$izin) {
            return;
        }

        DB::table('permission_user')
            ->where('permission_id', $izin->id)
            ->whereIn('user_id', DB::table('users')->whereIn('role', ['direktur1', 'direktur2'])->pluck('id'))
            ->delete();
    }
};
