<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('embs')->nullable()->after('tax_id');
            $table->string('logo_path')->nullable()->after('phone');
            $table->string('bank_name')->nullable()->after('logo_path');
            $table->string('bank_account')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['embs', 'logo_path', 'bank_name', 'bank_account']);
        });
    }
};
