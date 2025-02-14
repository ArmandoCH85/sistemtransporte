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
        Schema::create('request_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests');
            $table->foreignId('user_id')->constrained('users'); // User who registered the status change
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']); // Cambiar a enum
            $table->text('comment')->nullable();
            $table->boolean('notified')->default(false);
            $table->index(['request_id', 'created_at']); // Añadir índice compuesto
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_statuses');
    }
};
