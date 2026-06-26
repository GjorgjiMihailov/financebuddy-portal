<?php

use App\Enums\JournalEntryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('description', 500);
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default(JournalEntryStatus::Draft->value);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
