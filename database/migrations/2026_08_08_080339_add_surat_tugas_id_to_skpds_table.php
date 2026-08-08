<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->foreignId('surat_tugas_id')->nullable()->after('id')->constrained('surat_tugas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->dropForeign(['surat_tugas_id']);
            $table->dropColumn('surat_tugas_id');
        });
    }
};
