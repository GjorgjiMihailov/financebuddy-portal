<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('kontragent_id')->nullable()->after('company_id')->constrained('kontragenti')->nullOnDelete();
            $table->date('due_date')->nullable()->after('date');
            $table->text('notes')->nullable()->after('due_date');
            $table->decimal('subtotal', 14, 2)->default(0)->after('notes');
            $table->decimal('vat_total', 14, 2)->default(0)->after('subtotal');

            // client_name е веќе nullable во некои Laravel верзии, но да ја правиме nullable
            $table->string('client_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['kontragent_id']);
            $table->dropColumn(['kontragent_id', 'due_date', 'notes', 'subtotal', 'vat_total']);
        });
    }
};
