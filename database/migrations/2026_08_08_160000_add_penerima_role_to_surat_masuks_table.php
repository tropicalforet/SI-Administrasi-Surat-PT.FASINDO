<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Surat masuk kini dapat ditujukan ke sebuah role, bukan hanya ke satu
     * pengguna. Semua pengguna dengan role tersebut berhak membacanya.
     */
    public function up(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('penerima_role')->nullable()->after('penerima_id');
            $table->index('penerima_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropIndex(['penerima_role']);
            $table->dropColumn('penerima_role');
        });
    }
};
