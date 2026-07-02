<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreignId('kontragent_id')
                ->nullable()
                ->after('account_code')
                ->constrained('kontragenti')
                ->nullOnDelete();
            $table->date('line_date')->nullable()->after('kontragent_id');
            $table->string('closing_reference', 100)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['kontragent_id']);
            $table->dropColumn(['kontragent_id', 'line_date', 'closing_reference']);
        });
    }
};