<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            // Drop FK first so we can change the column
            $table->dropForeign(['account_code']);
            $table->string('account_code', 10)->nullable()->change();
            // Re-add FK allowing nulls
            $table->foreign('account_code')
                ->references('code')
                ->on('chart_of_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['account_code']);
            $table->string('account_code', 10)->nullable(false)->change();
            $table->foreign('account_code')
                ->references('code')
                ->on('chart_of_accounts');
        });
    }
};