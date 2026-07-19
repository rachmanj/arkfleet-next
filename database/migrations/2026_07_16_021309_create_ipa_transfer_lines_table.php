<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipa_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ipa_transfer_id')->constrained('ipa_transfers')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->string('unit_no', 50);
            $table->string('unit_code', 50)->nullable();
            $table->string('from_project_code', 20)->nullable();
            $table->string('to_project_code', 20)->nullable();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipa_transfer_lines');
    }
};
