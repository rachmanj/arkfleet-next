<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_name', 100);
            $table->string('akronim', 10)->nullable();
            $table->string('sap_code', 20)->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_selectable')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
