<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedSmallInteger('group_code')->nullable()->after('company_id');
            $table->unsignedSmallInteger('year')->nullable()->after('group_code');
            $table->unsignedInteger('sequence_number')->nullable()->after('year');

            $table->foreign('group_code')
                ->references('code')
                ->on('journal_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['group_code']);
            $table->dropColumn(['group_code', 'year', 'sequence_number']);
        });
    }
};