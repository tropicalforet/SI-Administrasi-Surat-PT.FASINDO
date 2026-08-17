<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laporan Disposisi kini dibatasi jabatan, bukan izin per pengguna.
     *
     * Izin 'akses_laporan_disposisi' karenanya tidak lagi mengontrol apa pun:
     * Dirut, Sekretaris, dan administrator masuk lewat jabatannya, sedangkan
     * pemegang izin lain tetap ditolak. Membiarkannya berarti menyisakan
     * centang palsu di menu Manajemen User - tampak memberi akses padahal
     * tidak. Kedua Direktur bidang memegangnya saat migrasi ini ditulis.
     */
    public function up(): void
    {
        $izin = DB::table('permissions')->where('name', 'akses_laporan_disposisi')->first();

        if (!$izin) {
            return;
        }

        DB::table('permission_user')->where('permission_id', $izin->id)->delete();
        DB::table('permissions')->where('id', $izin->id)->delete();
    }

    public function down(): void
    {
        $sudahAda = DB::table('permissions')->where('name', 'akses_laporan_disposisi')->exists();

        if ($sudahAda) {
            return;
        }

        // Pemegang lamanya tidak dapat dipulihkan - hanya definisi izinnya.
        DB::table('permissions')->insert([
            'name'       => 'akses_laporan_disposisi',
            'label'      => 'Lap. Disposisi',
            'group'      => 'Laporan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
