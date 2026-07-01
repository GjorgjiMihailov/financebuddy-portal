<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'initial']);
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->string('reference_type')->nullable(); // purchase_invoice, sales_invoice, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->date('movement_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['warehouse_id', 'item_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');
    }
};
