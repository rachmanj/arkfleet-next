<?php

namespace App\Services\Sap;

use App\Models\SapPostingLog;
use Illuminate\Support\Facades\DB;

class PostingService
{
    public function __construct(
        protected SapService $sapService,
    ) {}

    public function hasSuccessfulPost(string $idempotencyKey): bool
    {
        return SapPostingLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'success')
            ->exists();
    }

    public function logAttempt(
        string $documentType,
        string $idempotencyKey,
        string $status,
        ?int $docEntry = null,
        ?int $docNum = null,
        ?array $errorPayload = null,
        ?int $userId = null,
    ): SapPostingLog {
        return SapPostingLog::query()->create([
            'document_type' => $documentType,
            'idempotency_key' => $idempotencyKey,
            'status' => $status,
            'doc_entry' => $docEntry,
            'doc_num' => $docNum,
            'error_payload' => $errorPayload,
            'posted_by' => $userId,
            'posted_at' => $status === 'success' ? now() : null,
        ]);
    }

    public function postWithIdempotency(
        string $documentType,
        string $idempotencyKey,
        callable $postCallback,
        ?int $userId = null,
    ): SapPostingLog {
        if ($this->hasSuccessfulPost($idempotencyKey)) {
            return SapPostingLog::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('status', 'success')
                ->firstOrFail();
        }

        return DB::transaction(function () use ($documentType, $idempotencyKey, $postCallback, $userId) {
            if ($this->hasSuccessfulPost($idempotencyKey)) {
                return SapPostingLog::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->where('status', 'success')
                    ->firstOrFail();
            }

            try {
                $result = $postCallback($this->sapService);

                return $this->recordAttempt(
                    documentType: $documentType,
                    idempotencyKey: $idempotencyKey,
                    status: 'success',
                    docEntry: $result['DocEntry'] ?? null,
                    docNum: $result['DocNum'] ?? null,
                    userId: $userId,
                );
            } catch (\Throwable $exception) {
                return $this->recordAttempt(
                    documentType: $documentType,
                    idempotencyKey: $idempotencyKey,
                    status: 'failed',
                    errorPayload: [
                        'message' => $exception->getMessage(),
                        'class' => $exception::class,
                    ],
                    userId: $userId,
                );
            }
        });
    }

    protected function recordAttempt(
        string $documentType,
        string $idempotencyKey,
        string $status,
        ?int $docEntry = null,
        ?int $docNum = null,
        ?array $errorPayload = null,
        ?int $userId = null,
    ): SapPostingLog {
        $data = [
            'document_type' => $documentType,
            'status' => $status,
            'doc_entry' => $docEntry,
            'doc_num' => $docNum,
            'error_payload' => $errorPayload,
            'posted_by' => $userId,
            'posted_at' => $status === 'success' ? now() : null,
        ];

        $log = SapPostingLog::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($log) {
            $log->update($data);

            return $log->fresh();
        }

        return SapPostingLog::query()->create([
            'idempotency_key' => $idempotencyKey,
            ...$data,
        ]);
    }
}
