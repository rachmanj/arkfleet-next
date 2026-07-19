<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depreciation_run_id')->constrained('depreciation_runs')->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->string('book_type', 10);
            $table->date('period_date');
            $table->decimal('opening_nbv', 18, 2);
            $table->decimal('depreciation_amount', 18, 2);
            $table->decimal('accumulated_depreciation', 18, 2);
            $table->decimal('closing_nbv', 18, 2);
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'book_type', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
