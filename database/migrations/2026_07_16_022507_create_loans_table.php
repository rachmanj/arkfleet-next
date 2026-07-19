<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_card_code', 20);
            $table->string('contract_number', 50);
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->unsignedSmallInteger('term_months');
            $table->string('currency', 10)->default('IDR');
            $table->string('principal_gl', 20)->default('22201017');
            $table->string('interest_gl', 20)->default('71201004');
            $table->string('tax_code', 20)->default('B100');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('project_code', 20)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('schedule_locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('contract_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
