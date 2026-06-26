<?php

use App\Enums\DocumentStatus;
use App\Enums\IntakeChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('type', 30);
            $table->string('status', 30)->default(DocumentStatus::Pending->value);
            $table->string('intake_channel', 20)->default(IntakeChannel::Portal->value);
            $table->string('original_filename');
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->json('ai_raw_response')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->timestamp('ai_processed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
