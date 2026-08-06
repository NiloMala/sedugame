<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_skills', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['campaign_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_skills');
    }
};
