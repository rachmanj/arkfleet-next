<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_posting_logs', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('idempotency_key');
            $table->string('status');
            $table->unsignedBigInteger('doc_entry')->nullable();
            $table->unsignedBigInteger('doc_num')->nullable();
            $table->json('error_payload')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['document_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_posting_logs');
    }
};
