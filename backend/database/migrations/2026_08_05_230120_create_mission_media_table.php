<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_media', function (Blueprint $table) {
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->primary(['mission_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_media');
    }
};
