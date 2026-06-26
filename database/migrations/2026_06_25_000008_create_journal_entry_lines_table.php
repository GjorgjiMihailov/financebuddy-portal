<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('account_code', 10);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->foreign('account_code')
                ->references('code')
                ->on('chart_of_accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
