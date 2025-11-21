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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_name');
            $table->string('tenant_type')->comment('food_truck, booth, space_only');
            $table->string('tenant_phone')->nullable();
            $table->text('tenant_address')->nullable();
            $table->string('booth_num')->nullable();
            $table->string('area_sm')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
