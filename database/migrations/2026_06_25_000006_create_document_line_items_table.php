<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('suggested_account_code', 10)->nullable();
            $table->string('confirmed_account_code', 10)->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('suggested_account_code')
                ->references('code')
                ->on('chart_of_accounts')
                ->nullOnDelete();

            $table->foreign('confirmed_account_code')
                ->references('code')
                ->on('chart_of_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_line_items');
    }
};
