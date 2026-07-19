<?php

namespace App\Services\Depreciation;

use App\Models\AssetDisposal;
use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetDisposalService
{
    public function dispose(FixedAsset $asset, array $data, int $userId): AssetDisposal
    {
        if ($asset->status === 'disposed') {
            throw ValidationException::withMessages([
                'asset' => 'Asset is already disposed.',
            ]);
        }

        $disposalDate = Carbon::parse($data['disposal_date']);

        return DB::transaction(function () use ($asset, $data, $userId, $disposalDate) {
            $bookNbv = $this->nbvAtDate($asset, 'book', $disposalDate);
            $taxNbv = $this->nbvAtDate($asset, 'tax', $disposalDate);
            $proceeds = (float) ($data['proceeds'] ?? 0);

            $disposal = AssetDisposal::create([
                'fixed_asset_id' => $asset->id,
                'disposal_date' => $disposalDate,
                'disposal_type' => $data['disposal_type'],
                'proceeds' => $proceeds,
                'book_nbv_at_disposal' => $bookNbv,
                'tax_nbv_at_disposal' => $taxNbv,
                'book_gain_loss' => round($proceeds - $bookNbv, 2),
                'tax_gain_loss' => round($proceeds - $taxNbv, 2),
                'notes' => $data['notes'] ?? null,
                'disposed_by' => $userId,
            ]);

            $asset->update([
                'status' => 'disposed',
                'disposed_at' => $disposalDate,
            ]);

            return $disposal->load('fixedAsset.equipment');
        });
    }

    protected function nbvAtDate(FixedAsset $asset, string $bookType, Carbon $date): float
    {
        $entry = DepreciationEntry::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('book_type', $bookType)
            ->whereDate('period_date', '<=', $date)
            ->orderByDesc('period_date')
            ->first();

        return $entry
            ? (float) $entry->closing_nbv
            : (float) $asset->acquisition_cost;
    }
}
