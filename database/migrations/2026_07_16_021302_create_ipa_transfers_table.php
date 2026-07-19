<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipa_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('from_project_code', 20)->nullable();
            $table->string('to_project_code', 20)->nullable();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamp('transferred_at');
            $table->text('notes')->nullable();
            $table->unsignedInteger('line_count')->default(0);
            $table->timestamps();

            $table->index('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipa_transfers');
    }
};
