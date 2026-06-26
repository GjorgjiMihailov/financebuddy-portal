<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('name');
            $table->tinyInteger('class')->unsigned();
            $table->string('account_type', 20);
            $table->string('parent_code', 10)->nullable();
            $table->boolean('allows_posting')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_code')
                ->references('code')
                ->on('chart_of_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
