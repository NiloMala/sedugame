<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->after('password');
            $table->string('avatar_url')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_url');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['status', 'avatar_url', 'last_login_at', 'deleted_at']);
        });
    }
};
