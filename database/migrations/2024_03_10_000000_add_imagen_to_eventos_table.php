<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el nombre del archivo de la imagen del evento (jpg, png o webp).
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('numMaxEntradasPorPersona');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
