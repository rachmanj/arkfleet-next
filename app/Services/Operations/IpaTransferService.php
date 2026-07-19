<?php

namespace App\Services\Operations;

use App\Models\CartItem;
use App\Models\Equipment;
use App\Models\IpaTransfer;
use App\Models\IpaTransferLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IpaTransferService
{
    public function submitTransfer(int $userId, array $data): IpaTransfer
    {
        $cartItems = CartItem::query()
            ->with(['equipment'])
            ->where('user_id', $userId)
            ->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your transfer cart is empty.',
            ]);
        }

        return DB::transaction(function () use ($userId, $data, $cartItems) {
            $transfer = IpaTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'user_id' => $userId,
                'from_project_code' => $data['from_project_code'] ?? null,
                'to_project_code' => $data['to_project_code'],
                'from_department_id' => $data['from_department_id'] ?? null,
                'to_department_id' => $data['to_department_id'] ?? null,
                'transferred_at' => now(),
                'notes' => $data['notes'] ?? null,
                'line_count' => $cartItems->count(),
            ]);

            foreach ($cartItems as $item) {
                /** @var Equipment $equipment */
                $equipment = $item->equipment;

                IpaTransferLine::create([
                    'ipa_transfer_id' => $transfer->id,
                    'equipment_id' => $equipment->id,
                    'unit_code' => $equipment->unit_code,
                    'from_project_code' => $equipment->project_code,
                    'to_project_code' => $item->to_project_code ?? $data['to_project_code'],
                    'from_department_id' => $equipment->department_id,
                    'to_department_id' => $item->to_department_id ?? $data['to_department_id'] ?? null,
                ]);

                $equipment->update([
                    'project_code' => $item->to_project_code ?? $data['to_project_code'],
                    'department_id' => $item->to_department_id ?? $data['to_department_id'] ?? $equipment->department_id,
                ]);
            }

            CartItem::query()->where('user_id', $userId)->delete();

            return $transfer->load(['lines', 'user', 'fromDepartment', 'toDepartment']);
        });
    }

    public function generateTransferNumber(): string
    {
        $prefix = 'IPA-'.now()->format('Ymd');
        $latest = IpaTransfer::query()
            ->where('transfer_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('transfer_number');

        $sequence = 1;

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%03d', $prefix, $sequence);
    }

    public function transferPdf(IpaTransfer $transfer)
    {
        $transfer->load(['lines.fromDepartment', 'lines.toDepartment', 'lines.equipment', 'user', 'fromDepartment', 'toDepartment']);

        return Pdf::loadView('pdf.ipa-transfer', [
            'transfer' => $transfer,
        ])->setPaper('a4', 'portrait');
    }
}
