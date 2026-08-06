<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('city', 120)->nullable();
            $table->char('state', 2)->nullable();
            $table->string('country', 80)->default('Brasil');
            $table->string('biome', 80)->nullable();
            $table->string('historical_period', 80)->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('source_url')->nullable();
            $table->string('license', 120)->nullable();
            $table->string('attribution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
