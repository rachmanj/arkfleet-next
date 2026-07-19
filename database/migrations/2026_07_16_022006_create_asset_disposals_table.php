<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('disposal_date');
            $table->string('disposal_type', 20);
            $table->decimal('proceeds', 18, 2)->nullable();
            $table->decimal('book_nbv_at_disposal', 18, 2);
            $table->decimal('tax_nbv_at_disposal', 18, 2);
            $table->decimal('book_gain_loss', 18, 2);
            $table->decimal('tax_gain_loss', 18, 2);
            $table->text('notes')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
