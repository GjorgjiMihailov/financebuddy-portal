<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('kontragenti', 'kooperanti');

        DB::statement('ALTER TABLE sales_invoices RENAME COLUMN kontragent_id TO kooperant_id');
        DB::statement('ALTER TABLE purchase_invoices RENAME COLUMN kontragent_id TO kooperant_id');
        DB::statement('ALTER TABLE journal_entry_lines RENAME COLUMN kontragent_id TO kooperant_id');
        DB::statement('ALTER TABLE document_line_items RENAME COLUMN suggested_kontragent_id TO suggested_kooperant_id');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE document_line_items RENAME COLUMN suggested_kooperant_id TO suggested_kontragent_id');
        DB::statement('ALTER TABLE journal_entry_lines RENAME COLUMN kooperant_id TO kontragent_id');
        DB::statement('ALTER TABLE purchase_invoices RENAME COLUMN kooperant_id TO kontragent_id');
        DB::statement('ALTER TABLE sales_invoices RENAME COLUMN kooperant_id TO kontragent_id');

        Schema::rename('kooperanti', 'kontragenti');
    }
};
