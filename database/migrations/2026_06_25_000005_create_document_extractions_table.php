<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_tax_id', 20)->nullable();
            $table->string('vendor_vat_number', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_tax_id', 20)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->date('due_date')->nullable();
            $table->char('currency', 3)->default('MKD');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extractions');
    }
};
