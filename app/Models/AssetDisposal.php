<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'disposal_date',
        'disposal_type',
        'proceeds',
        'book_nbv_at_disposal',
        'tax_nbv_at_disposal',
        'book_gain_loss',
        'tax_gain_loss',
        'notes',
        'disposed_by',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'proceeds' => 'decimal:2',
            'book_nbv_at_disposal' => 'decimal:2',
            'tax_nbv_at_disposal' => 'decimal:2',
            'book_gain_loss' => 'decimal:2',
            'tax_gain_loss' => 'decimal:2',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function disposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }
}
