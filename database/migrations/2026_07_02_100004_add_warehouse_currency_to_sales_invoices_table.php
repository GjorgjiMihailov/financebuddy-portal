<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->date('date_of_supply')->nullable()->after('date');
            $table->string('currency', 3)->default('MKD')->after('total_amount');
            $table->decimal('exchange_rate', 12, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['warehouse_id', 'date_of_supply', 'currency', 'exchange_rate']);
        });
    }
};
