<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('unit_no', 50)->unique();
            $table->string('unit_code', 50)->nullable();
            $table->string('description')->nullable();
            $table->foreignId('unit_model_id')->nullable()->constrained('unit_models')->nullOnDelete();
            $table->foreignId('manufacture_id')->nullable()->constrained('manufactures')->nullOnDelete();
            $table->foreignId('plant_type_id')->nullable()->constrained('plant_types')->nullOnDelete();
            $table->foreignId('plant_group_id')->nullable()->constrained('plant_groups')->nullOnDelete();
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('unitstatus_id')->nullable()->constrained('unitstatuses')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('project_code', 20)->nullable()->index();
            $table->decimal('acquisition_cost', 18, 2)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->date('in_service_date')->nullable();
            $table->decimal('salvage_value', 18, 2)->nullable();
            $table->unsignedSmallInteger('useful_life_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
