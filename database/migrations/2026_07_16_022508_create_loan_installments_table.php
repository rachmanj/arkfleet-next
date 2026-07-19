<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->unsignedSmallInteger('total_installments');
            $table->date('due_date');
            $table->date('posting_date')->nullable();
            $table->date('document_date')->nullable();
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_amount', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->string('principal_gl', 20)->nullable();
            $table->string('interest_gl', 20)->nullable();
            $table->string('tax_code', 20)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('project_code', 20)->nullable();
            $table->string('faktur_pajak_no', 50)->nullable();
            $table->date('faktur_pajak_date')->nullable();
            $table->string('vendor_ref_no', 50)->nullable();
            $table->unsignedInteger('sap_doc_entry')->nullable();
            $table->unsignedInteger('sap_doc_num')->nullable();
            $table->unsignedInteger('sap_payment_doc_entry')->nullable();
            $table->unsignedInteger('sap_payment_doc_num')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->unique(['loan_id', 'installment_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
