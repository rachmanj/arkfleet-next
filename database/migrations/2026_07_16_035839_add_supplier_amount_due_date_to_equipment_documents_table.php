<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_documents', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('document_type_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('amount', 18, 2)->nullable()->after('expiry_date');
            $table->date('due_date')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['amount', 'due_date']);
        });
    }
};
