<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('package_image')->nullable()->default(null)->after('comments');
            $table->string('pickup_location')->nullable()->default(null)->after('pickup_address');
            $table->string('delivery_location')->nullable()->default(null)->after('delivery_address');
        });
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn([
                'package_image',
                'pickup_location',
                'delivery_location'
            ]);
        });
    }
};
