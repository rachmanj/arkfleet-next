<?php

use App\Models\IpaTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ipa_transfers', function (Blueprint $table) {
            $table->string('ipa_no', 30)->nullable()->after('transfer_number');
            $table->date('ipa_date')->nullable()->after('ipa_no');
            $table->string('tujuan_row_1')->nullable()->after('to_department_id');
            $table->string('tujuan_row_2')->nullable()->after('tujuan_row_1');
            $table->string('cc_row_1')->nullable()->after('tujuan_row_2');
            $table->string('cc_row_2')->nullable()->after('cc_row_1');
            $table->string('cc_row_3')->nullable()->after('cc_row_2');
            $table->string('status', 20)->default('DRAFT')->after('cc_row_3');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('transferred_at')->nullable()->change();
        });

        IpaTransfer::query()->chunkById(100, function ($transfers) {
            foreach ($transfers as $transfer) {
                DB::table('ipa_transfers')->where('id', $transfer->id)->update([
                    'ipa_no' => $transfer->transfer_number,
                    'ipa_date' => $transfer->transferred_at?->toDateString() ?? $transfer->created_at->toDateString(),
                    'status' => 'SUBMITTED',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ipa_transfers', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'ipa_no',
                'ipa_date',
                'tujuan_row_1',
                'tujuan_row_2',
                'cc_row_1',
                'cc_row_2',
                'cc_row_3',
                'status',
                'approved_by',
                'approved_at',
            ]);
            $table->timestamp('transferred_at')->nullable(false)->change();
        });
    }
};
