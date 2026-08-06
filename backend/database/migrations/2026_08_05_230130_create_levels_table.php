<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedInteger('minimum_experience');
            $table->unsignedInteger('maximum_experience')->nullable();
            $table->string('icon', 60)->nullable();
            $table->unsignedTinyInteger('order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
