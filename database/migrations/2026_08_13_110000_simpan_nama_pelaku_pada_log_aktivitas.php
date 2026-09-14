<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Log aktivitas menyimpan nama pelakunya, bukan hanya penunjuk ke tabel users.
 *
 * Nilai sebuah audit trail terletak pada "siapa mengerjakan apa". Begitu
 * pengguna dihapus, penunjuk user_id menjadi kosong dan seluruh riwayatnya
 * berubah menjadi tak bertuan. Nama pelaku karena itu direkam saat kejadian,
 * sehingga tetap terbaca meski orangnya sudah tidak tercatat lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('nama_pengguna')->nullable()->after('user_id');
        });

        // Isi baris lama dari tabel users selagi penunjuknya masih utuh.
        // Dikerjakan lewat PHP karena UPDATE ... JOIN hanya dikenal MySQL.
        $nama = DB::table('users')->pluck('name', 'id');

        foreach ($nama as $id => $n) {
            DB::table('activity_logs')
                ->where('user_id', $id)
                ->update(['nama_pengguna' => $n]);
        }
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('nama_pengguna');
        });
    }
};
