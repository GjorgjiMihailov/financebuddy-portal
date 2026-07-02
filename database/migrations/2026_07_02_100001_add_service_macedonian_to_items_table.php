<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_service')->default(false)->after('is_active');
            $table->boolean('is_macedonian')->default(false)->after('is_service');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['is_service', 'is_macedonian']);
        });
    }
};
