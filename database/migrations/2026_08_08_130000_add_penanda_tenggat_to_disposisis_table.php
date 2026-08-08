<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Penanda kapan pengingat dan eskalasi sudah dikirim, agar perintah
     * pemeriksaan tenggat yang berjalan setiap hari tidak mengirim
     * notifikasi yang sama berulang kali.
     */
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->timestamp('diingatkan_pada')->nullable()->after('batas_waktu');
            $table->timestamp('dieskalasi_pada')->nullable()->after('diingatkan_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn(['diingatkan_pada', 'dieskalasi_pada']);
        });
    }
};
