<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontragenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('edb', 13);
            $table->string('embs', 7)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_vat_payer')->default(false);
            $table->enum('type', ['client', 'supplier', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'edb']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontragenti');
    }
};
