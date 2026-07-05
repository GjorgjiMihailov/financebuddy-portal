<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_line_items', function (Blueprint $table) {
            $table->foreignId('suggested_kontragent_id')->nullable()->after('suggested_account_code')
                ->constrained('kontragenti')->nullOnDelete();
            $table->string('suggested_closing_reference', 100)->nullable()->after('suggested_kontragent_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_line_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_kontragent_id');
            $table->dropColumn('suggested_closing_reference');
        });
    }
};
