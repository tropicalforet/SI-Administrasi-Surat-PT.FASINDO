<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Penanda kapan penerima disposisi induk diberi tahu bahwa seluruh
     * disposisi lanjutannya sudah selesai dan tinggal dikonfirmasi, agar
     * pemberitahuan itu tidak terkirim berulang.
     */
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->timestamp('siap_konfirmasi_pada')->nullable()->after('dieskalasi_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn('siap_konfirmasi_pada');
        });
    }
};
