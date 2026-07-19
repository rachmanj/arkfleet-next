<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentHmKmUploadBatch extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'batch_id';

    protected $table = 'equipment_hm_km_upload_batches';

    protected $fillable = [
        'batch_id',
        'original_filename',
        'rows_total',
        'rows_imported',
        'rows_skipped',
        'rows_errored',
        'errors',
        'uploaded_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(EquipmentHmKmReading::class, 'upload_batch_id', 'batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
