<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('book_scope', 10)->default('all');
            $table->string('status', 20)->default('draft');
            $table->decimal('total_book_depreciation', 18, 2)->default(0);
            $table->decimal('total_tax_depreciation', 18, 2)->default(0);
            $table->unsignedInteger('entry_count')->default(0);
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['period_year', 'period_month', 'book_scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_runs');
    }
};
