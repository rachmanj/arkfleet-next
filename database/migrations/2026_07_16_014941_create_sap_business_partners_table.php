<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('card_code', 50)->unique();
            $table->string('card_name');
            $table->string('card_type', 1)->index();
            $table->boolean('is_active')->default(true);
            $table->string('federal_tax_id', 50)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('credit_limit', 18, 2)->nullable();
            $table->decimal('balance', 18, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_business_partners');
    }
};
