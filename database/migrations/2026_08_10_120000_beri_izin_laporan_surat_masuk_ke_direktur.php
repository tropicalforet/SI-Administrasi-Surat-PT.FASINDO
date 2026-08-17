<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laporan Surat Masuk dibuka untuk jajaran direksi.
     *
     * Dirut dan Sekretaris sudah masuk lewat pintasan role di
     * User::hasPermission, tetapi Direktur bidang bergantung pada izin yang
     * ditetapkan per pengguna. Tanpa migrasi ini mereka tetap tertahan 403
     * meski aturannya sudah diubah di kode.
     *
     * Direktur yang dibuat setelah ini mendapatkan izinnya lewat menu
     * Manajemen User seperti izin lainnya.
     */
    public function up(): void
    {
        $izin = DB::table('permissions')->where('name', 'akses_laporan_surat_masuk')->first();

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
        $izin = DB::table('permissions')->where('name', 'akses_laporan_surat_masuk')->first();

        if (!$izin) {
            return;
        }

        DB::table('permission_user')
            ->where('permission_id', $izin->id)
            ->whereIn('user_id', DB::table('users')->whereIn('role', ['direktur1', 'direktur2'])->pluck('id'))
            ->delete();
    }
};
