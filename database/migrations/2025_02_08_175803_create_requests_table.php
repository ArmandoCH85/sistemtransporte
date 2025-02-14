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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->index();
            $table->foreignId('material_category_id')->constrained('material_categories')->index();
            $table->foreignId('origin_area_id')->constrained('areas')->index();
            $table->text('material_description');
            $table->text('comments')->nullable();
            $table->boolean('fields_completed')->default(false)->index();
            $table->string('pickup_address');
            $table->string('pickup_contact');
            $table->string('pickup_phone');
            $table->string('delivery_address');
            $table->string('delivery_contact');
            $table->string('delivery_phone');
            $table->string('current_status')->default('pending'); // e.g., "pending", "accepted", "completed"
            $table->index(['current_status', 'requester_id']);
            $table->index(['current_status', 'created_at']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requests');
    }
};
