<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisis', function (Blueprint $table) {

            $table->id();

            $table->foreignId('surat_masuk_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('dari_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('kepada_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->text('catatan_disposisi')->nullable();

            $table->text('catatan_tindak_lanjut')->nullable();

            $table->enum('status', [
                'menunggu',
                'diproses',
                'selesai'
            ])->default('menunggu');

            $table->timestamp('tanggal_disposisi');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisis');
    }
};