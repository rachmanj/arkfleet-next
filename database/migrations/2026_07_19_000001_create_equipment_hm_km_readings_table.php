<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_hm_km_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('reading_date');
            $table->string('reading_type');
            $table->decimal('reading_value', 12, 2);
            $table->string('source')->default('manual');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('upload_batch_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['equipment_id', 'reading_type', 'reading_date']);
            $table->index('upload_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_hm_km_readings');
    }
};
