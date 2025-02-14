<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega la columna evidence_image a la tabla requests
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('evidence_image')->nullable()->after('current_status');
        });
    }

    /**
     * Reverse the migrations.
     * Elimina la columna evidence_image de la tabla requests
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('evidence_image');
        });
    }
};
