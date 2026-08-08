<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SKPD tidak mencantumkan rincian biaya perjalanan, sehingga kolom-kolom
     * ini tidak pernah diisi maupun ditampilkan. Dihapus agar skema sesuai
     * dengan dokumen yang sebenarnya diterbitkan.
     */
    public function up(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->dropColumn([
                'biaya_transport',
                'biaya_penginapan',
                'biaya_konsumsi_per_hari',
                'total_biaya',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->decimal('biaya_transport', 15, 2)->default(0)->after('durasi_hari');
            $table->decimal('biaya_penginapan', 15, 2)->default(0)->after('biaya_transport');
            $table->decimal('biaya_konsumsi_per_hari', 15, 2)->default(0)->after('biaya_penginapan');
            $table->decimal('total_biaya', 15, 2)->default(0)->after('biaya_konsumsi_per_hari');
        });
    }
};
