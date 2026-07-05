<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank-statement header fields on document_extractions
        Schema::table('document_extractions', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('currency');
            $table->string('account_number')->nullable()->after('bank_name');
            $table->string('statement_number')->nullable()->after('account_number');
            $table->decimal('opening_balance', 14, 2)->nullable()->after('statement_number');
            $table->decimal('closing_balance', 14, 2)->nullable()->after('opening_balance');
            $table->decimal('total_debit', 14, 2)->nullable()->after('closing_balance');
            $table->decimal('total_credit', 14, 2)->nullable()->after('total_debit');
        });

        // Per-transaction fields on document_line_items
        Schema::table('document_line_items', function (Blueprint $table) {
            $table->decimal('debit', 14, 2)->nullable()->after('total_amount');
            $table->decimal('credit', 14, 2)->nullable()->after('debit');
            $table->date('transaction_date')->nullable()->after('credit');
            $table->string('reference')->nullable()->after('transaction_date');
        });

        // Parent-document link (for split multi-statement PDFs)
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_document_id')->nullable()->after('company_id');
            $table->foreign('parent_document_id')->references('id')->on('documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['parent_document_id']);
            $table->dropColumn('parent_document_id');
        });

        Schema::table('document_line_items', function (Blueprint $table) {
            $table->dropColumn(['debit', 'credit', 'transaction_date', 'reference']);
        });

        Schema::table('document_extractions', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_number', 'statement_number',
                                'opening_balance', 'closing_balance', 'total_debit', 'total_credit']);
        });
    }
};