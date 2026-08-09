<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Saat SoftDeletes diberlakukan, surat masuk yang dihapus tidak lagi ikut
     * menghapus disposisinya seperti dulu dilakukan foreign key cascade.
     * Disposisi yang telanjur tertinggal menunjuk surat tersembunyi membuat
     * halaman "Disposisi Saya" gagal dimuat, jadi dirapikan di sini.
     */
    public function up(): void
    {
        $terpengaruh = DB::table('disposisis')
            ->join('surat_masuks', 'disposisis.surat_masuk_id', '=', 'surat_masuks.id')
            ->whereNull('disposisis.deleted_at')
            ->whereNotNull('surat_masuks.deleted_at')
            ->select('disposisis.id', 'surat_masuks.deleted_at as dihapus_pada')
            ->get();

        foreach ($terpengaruh as $baris) {
            DB::table('disposisis')
                ->where('id', $baris->id)
                ->update(['deleted_at' => $baris->dihapus_pada]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Tidak dapat dibalik dengan aman: setelah dirapikan, disposisi ini tidak
     * dapat dibedakan lagi dari yang memang dihapus tersendiri.
     */
    public function down(): void
    {
        //
    }
};
