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
        Schema::create('request_transporter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests');
            $table->foreignId('transporter_id')->constrained('users'); // Transporter user
            $table->enum('assignment_status', [
                'pending',
                'accepted',
                'rejected'
            ])->default('pending'); // e.g., "pending", "accepted", "rejected"
            $table->timestamp('assignment_date')->useCurrent();
            $table->timestamp('response_date')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            // Índices para mejorar el rendimiento de las consultas
            $table->index(['request_id', 'assignment_status']);
            $table->index(['transporter_id', 'assignment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_transporter');
    }
};
