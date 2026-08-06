<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'panorama_360', 'video', 'audio']);
            $table->string('title', 150)->nullable();
            $table->string('file_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('source', 120)->nullable();
            $table->string('license', 120)->nullable();
            $table->string('author', 120)->nullable();
            $table->string('attribution')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
