<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('requests', function (Blueprint $table) {
            // Solo agregar columnas si no existen
            if (!Schema::hasColumn('requests', 'package_image')) {
                $table->string('package_image')->nullable()->after('comments');
            }
            if (!Schema::hasColumn('requests', 'pickup_location')) {
                $table->string('pickup_location')->after('pickup_address');
            }
            if (!Schema::hasColumn('requests', 'delivery_location')) {
                $table->string('delivery_location')->after('delivery_address');
            }

            // Hacer nullable el material_category_id y origin_area_id
            $table->unsignedBigInteger('material_category_id')->nullable()->change();
            $table->string('origin_area_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['package_image', 'pickup_location', 'delivery_location']);
            $table->unsignedBigInteger('material_category_id')->nullable(false)->change();
            $table->string('origin_area_id')->nullable(false)->change();
        });
    }
};
