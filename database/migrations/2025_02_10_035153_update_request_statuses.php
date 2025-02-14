<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar los estados existentes al nuevo formato
        DB::table('requests')->where('current_status', 'PENDIENTE')->update(['current_status' => 'pending']);
        DB::table('requests')->where('current_status', 'ACEPTADO')->update(['current_status' => 'accepted']);
        DB::table('requests')->where('current_status', 'REPROGRAMADO')->update(['current_status' => 'rescheduled']);
        DB::table('requests')->where('current_status', 'FINALIZADO')->update(['current_status' => 'completed']);
        DB::table('requests')->where('current_status', 'FALLIDA')->update(['current_status' => 'failed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los estados al formato anterior
        DB::table('requests')->where('current_status', 'pending')->update(['current_status' => 'PENDIENTE']);
        DB::table('requests')->where('current_status', 'accepted')->update(['current_status' => 'ACEPTADO']);
        DB::table('requests')->where('current_status', 'rescheduled')->update(['current_status' => 'REPROGRAMADO']);
        DB::table('requests')->where('current_status', 'completed')->update(['current_status' => 'FINALIZADO']);
        DB::table('requests')->where('current_status', 'failed')->update(['current_status' => 'FALLIDA']);
    }
};
