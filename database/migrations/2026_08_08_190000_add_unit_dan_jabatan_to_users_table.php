<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bagan organisasi punya dua dimensi: level wewenang (vertikal) dan unit
     * kerja (horizontal). Satu kolom `role` tidak dapat mewakili keduanya,
     * sehingga enam jabatan dari dua direktorat sebelumnya terlebur menjadi
     * satu role 'staff'. Kolom `unit` memisahkan cabang organisasi, `jabatan`
     * menyimpan nama jabatan sebenarnya untuk ditampilkan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('role');
            $table->string('jabatan')->nullable()->after('unit');

            $table->index('unit');
        });

        // Direktur sudah berkorespondensi satu-satu dengan direktoratnya,
        // sehingga unit dan jabatannya dapat diisi dengan pasti.
        DB::table('users')->where('role', 'direktur1')->update([
            'unit'    => 'keuangan_administrasi',
            'jabatan' => 'Direktur Keuangan dan Administrasi',
        ]);

        DB::table('users')->where('role', 'direktur2')->update([
            'unit'    => 'teknik',
            'jabatan' => 'Direktur Teknik',
        ]);

        DB::table('users')->where('role', 'dirut')->update([
            'unit'    => 'pimpinan',
            'jabatan' => 'Direktur Utama',
        ]);

        DB::table('users')->where('role', 'sekretaris')->update([
            'unit'    => 'pimpinan',
            'jabatan' => 'Sekretaris',
        ]);

        // Pengguna berrole 'staff' sengaja TIDAK ditebak unitnya. Enam jabatan
        // pelaksana tersebar di dua direktorat dan menebak jabatan seseorang
        // berisiko salah, jadi unit mereka harus ditetapkan administrator.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['unit']);
            $table->dropColumn(['unit', 'jabatan']);
        });
    }
};
