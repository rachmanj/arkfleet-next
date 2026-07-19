<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('equipment')
            ->where(function ($query) {
                $query->whereNull('unit_code')->orWhere('unit_code', '');
            })
            ->update(['unit_code' => DB::raw('unit_no')]);

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropUnique(['unit_no']);
            $table->dropColumn('unit_no');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE equipment MODIFY unit_code VARCHAR(50) NOT NULL');
        }

        Schema::table('equipment', function (Blueprint $table) {
            $table->unique('unit_code');
        });

        if (Schema::hasColumn('ipa_transfer_lines', 'unit_no')) {
            DB::table('ipa_transfer_lines')
                ->where(function ($query) {
                    $query->whereNull('unit_code')->orWhere('unit_code', '');
                })
                ->update(['unit_code' => DB::raw('unit_no')]);

            Schema::table('ipa_transfer_lines', function (Blueprint $table) {
                $table->dropColumn('unit_no');
            });
        }

        if (Schema::hasColumn('unit_no_histories', 'old_unit_no')) {
            Schema::table('unit_no_histories', function (Blueprint $table) {
                $table->renameColumn('old_unit_no', 'old_unit_code');
                $table->renameColumn('new_unit_no', 'new_unit_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('unit_no_histories', 'old_unit_code')) {
            Schema::table('unit_no_histories', function (Blueprint $table) {
                $table->renameColumn('old_unit_code', 'old_unit_no');
                $table->renameColumn('new_unit_code', 'new_unit_no');
            });
        }

        Schema::table('ipa_transfer_lines', function (Blueprint $table) {
            $table->string('unit_no', 50)->nullable()->after('equipment_id');
        });

        DB::table('ipa_transfer_lines')->update(['unit_no' => DB::raw('unit_code')]);

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropUnique(['unit_code']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->string('unit_no', 50)->nullable()->after('id');
        });

        DB::table('equipment')->update(['unit_no' => DB::raw('unit_code')]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE equipment MODIFY unit_no VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE equipment MODIFY unit_code VARCHAR(50) NULL');
        }

        Schema::table('equipment', function (Blueprint $table) {
            $table->unique('unit_no');
        });
    }
};
