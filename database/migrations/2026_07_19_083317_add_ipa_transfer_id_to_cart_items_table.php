<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('ipa_transfer_id')->nullable()->after('user_id')
                ->constrained('ipa_transfers')->cascadeOnDelete();

            $table->dropUnique(['user_id', 'equipment_id']);
            $table->unique(['ipa_transfer_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['ipa_transfer_id', 'equipment_id']);
            $table->dropForeign(['ipa_transfer_id']);
            $table->dropColumn('ipa_transfer_id');

            $table->unique(['user_id', 'equipment_id']);
        });
    }
};
