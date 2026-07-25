<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {

            $table->enum('status', [
                'draft',
                'menunggu_direktur',
                'menunggu_dirut',
                'ditolak',
                'terkirim'
            ])->default('draft')->change();

            $table->foreignId('approved_direktur_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_direktur_at')
                ->nullable();

            $table->foreignId('approved_dirut_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_dirut_at')
                ->nullable();

            $table->text('catatan_revisi')
                ->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {

            $table->dropForeign(['approved_direktur_by']);
            $table->dropForeign(['approved_dirut_by']);

            $table->dropColumn([
                'approved_direktur_by',
                'approved_direktur_at',
                'approved_dirut_by',
                'approved_dirut_at',
                'catatan_revisi'
            ]);

        });
    }
};
