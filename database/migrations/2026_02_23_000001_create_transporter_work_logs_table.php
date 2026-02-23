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
        Schema::create('transporter_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->timestamps();

            $table->unique(['transporter_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transporter_work_logs');
    }
};
