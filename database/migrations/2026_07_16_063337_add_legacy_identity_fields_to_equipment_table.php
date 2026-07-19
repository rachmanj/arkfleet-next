<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('serial_no', 255)->nullable()->after('description');
            $table->string('chasis_no', 255)->nullable()->after('serial_no');
            $table->string('engine_model', 255)->nullable()->after('chasis_no');
            $table->string('machine_no', 255)->nullable()->after('engine_model');
            $table->string('nomor_polisi', 255)->nullable()->after('machine_no');
            $table->string('bahan_bakar', 255)->nullable()->after('nomor_polisi');
            $table->string('warna', 255)->nullable()->after('bahan_bakar');
            $table->decimal('capacity', 8, 2)->nullable()->after('warna');
            $table->text('remarks')->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn([
                'serial_no',
                'chasis_no',
                'engine_model',
                'machine_no',
                'nomor_polisi',
                'bahan_bakar',
                'warna',
                'capacity',
                'remarks',
            ]);
        });
    }
};
