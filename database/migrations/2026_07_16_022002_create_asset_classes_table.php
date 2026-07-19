<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('book_method', 20)->default('straight_line');
            $table->unsignedSmallInteger('book_useful_life_months')->default(60);
            $table->decimal('book_residual_rate', 8, 4)->default(0);
            $table->string('tax_group', 30)->default('kelompok_2');
            $table->string('tax_method', 20)->default('straight_line');
            $table->unsignedSmallInteger('tax_useful_life_months')->default(48);
            $table->decimal('tax_rate', 8, 4)->default(0.22);
            $table->string('sap_depreciation_gl', 20)->nullable();
            $table->string('sap_accumulated_gl', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_classes');
    }
};
