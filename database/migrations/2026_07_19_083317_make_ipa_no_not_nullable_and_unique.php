<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipa_transfers', function (Blueprint $table) {
            $table->string('ipa_no', 30)->nullable(false)->change();
            $table->unique('ipa_no');
            $table->date('ipa_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ipa_transfers', function (Blueprint $table) {
            $table->dropUnique(['ipa_no']);
            $table->string('ipa_no', 30)->nullable()->change();
            $table->date('ipa_date')->nullable()->change();
        });
    }
};
