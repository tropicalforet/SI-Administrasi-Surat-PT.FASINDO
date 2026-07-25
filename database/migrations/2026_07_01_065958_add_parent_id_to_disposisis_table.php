<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {

            $table->unsignedBigInteger('parent_disposisi_id')
                  ->nullable()
                  ->after('surat_masuk_id');

            $table->foreign('parent_disposisi_id')
                  ->references('id')
                  ->on('disposisis')
                  ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {

            $table->dropForeign(['parent_disposisi_id']);
            $table->dropColumn('parent_disposisi_id');

        });
    }
};