<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->unique()->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('asset_class_id')->constrained('asset_classes')->restrictOnDelete();
            $table->decimal('acquisition_cost', 18, 2);
            $table->date('acquisition_date')->nullable();
            $table->date('in_service_date');
            $table->decimal('salvage_value', 18, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->string('book_method', 20)->nullable();
            $table->unsignedSmallInteger('book_useful_life_months')->nullable();
            $table->decimal('book_residual_rate', 8, 4)->nullable();
            $table->string('tax_method', 20)->nullable();
            $table->unsignedSmallInteger('tax_useful_life_months')->nullable();
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->unsignedInteger('total_units')->nullable();
            $table->unsignedInteger('units_produced_to_date')->default(0);
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
