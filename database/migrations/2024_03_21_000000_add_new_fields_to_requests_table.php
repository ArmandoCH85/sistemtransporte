<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('package_image')->nullable()->after('comments');
            $table->string('pickup_location')->after('pickup_address');
            $table->string('delivery_location')->after('delivery_address');
        });
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('package_image');
            $table->dropColumn('pickup_location');
            $table->dropColumn('delivery_location');
        });
    }
};
