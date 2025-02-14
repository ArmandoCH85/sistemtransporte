<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('request_id')->constrained('requests');
        $table->foreignId('recipient_id')->constrained('users'); // User who receives the notification
        $table->string('notification_type'); // e.g., "status_change", "assignment"
        $table->text('message');
        $table->boolean('sent')->default(false);
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('sent_at')->nullable();
    });
}

public function down()
{
    Schema::dropIfExists('notifications');
}
};
