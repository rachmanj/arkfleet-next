<?php

namespace App\Services\Operations;

use App\Models\CartItem;
use App\Models\Equipment;
use App\Models\IpaTransfer;
use App\Models\IpaTransferLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IpaTransferService
{
    public function createIpa(int $userId, array $data): IpaTransfer
    {
        $transferNumber = $this->generateTransferNumber();
        $ipaNo = $data['ipa_no'] ?? $transferNumber;

        return IpaTransfer::create([
            'transfer_number' => $transferNumber,
            'ipa_no' => $ipaNo,
            'ipa_date' => $data['ipa_date'],
            'user_id' => $userId,
            'from_project_code' => $data['from_project_code'] ?? null,
            'to_project_code' => $data['to_project_code'],
            'from_department_id' => $data['from_department_id'] ?? null,
            'to_department_id' => $data['to_department_id'] ?? null,
            'tujuan_row_1' => $data['tujuan_row_1'],
            'tujuan_row_2' => $data['tujuan_row_2'] ?? null,
            'cc_row_1' => $data['cc_row_1'],
            'cc_row_2' => $data['cc_row_2'] ?? null,
            'cc_row_3' => $data['cc_row_3'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'DRAFT',
            'line_count' => 0,
        ]);
    }

    public function updateIpa(IpaTransfer $moving, array $data): IpaTransfer
    {
        $this->ensureDraft($moving);

        $moving->update([
            'ipa_no' => $data['ipa_no'],
            'ipa_date' => $data['ipa_date'],
            'from_project_code' => $data['from_project_code'] ?? null,
            'to_project_code' => $data['to_project_code'],
            'from_department_id' => $data['from_department_id'] ?? null,
            'to_department_id' => $data['to_department_id'] ?? null,
            'tujuan_row_1' => $data['tujuan_row_1'],
            'tujuan_row_2' => $data['tujuan_row_2'] ?? null,
            'cc_row_1' => $data['cc_row_1'],
            'cc_row_2' => $data['cc_row_2'] ?? null,
            'cc_row_3' => $data['cc_row_3'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $moving->fresh();
    }

    public function addEquipment(IpaTransfer $moving, int $userId, array $data): CartItem
    {
        $this->ensureDraft($moving);

        return CartItem::query()->updateOrCreate(
            [
                'ipa_transfer_id' => $moving->id,
                'equipment_id' => $data['equipment_id'],
            ],
            [
                'user_id' => $userId,
                'to_project_code' => $data['to_project_code'] ?? $moving->to_project_code,
                'to_department_id' => $data['to_department_id'] ?? $moving->to_department_id,
                'notes' => $data['notes'] ?? null,
            ],
        );
    }

    public function removeEquipment(CartItem $item): void
    {
        $this->ensureDraft($item->ipaTransfer);

        $item->delete();
    }

    public function submitIpa(IpaTransfer $moving): IpaTransfer
    {
        $this->ensureDraft($moving);

        $cartItems = CartItem::query()
            ->with(['equipment'])
            ->where('ipa_transfer_id', $moving->id)
            ->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Add at least one equipment item before submitting.',
            ]);
        }

        return DB::transaction(function () use ($moving, $cartItems) {
            foreach ($cartItems as $item) {
                /** @var Equipment $equipment */
                $equipment = $item->equipment;

                IpaTransferLine::create([
                    'ipa_transfer_id' => $moving->id,
                    'equipment_id' => $equipment->id,
                    'unit_no' => $equipment->unit_code,
                    'unit_code' => $equipment->unit_code,
                    'from_project_code' => $equipment->project_code,
                    'to_project_code' => $item->to_project_code ?? $moving->to_project_code,
                    'from_department_id' => $equipment->department_id,
                    'to_department_id' => $item->to_department_id ?? $moving->to_department_id,
                ]);

                $equipment->update([
                    'project_code' => $item->to_project_code ?? $moving->to_project_code,
                    'department_id' => $item->to_department_id ?? $moving->to_department_id ?? $equipment->department_id,
                ]);
            }

            $moving->update([
                'status' => 'SUBMITTED',
                'transferred_at' => now(),
                'line_count' => $cartItems->count(),
            ]);

            CartItem::query()->where('ipa_transfer_id', $moving->id)->delete();

            return $moving->load(['lines', 'user', 'fromDepartment', 'toDepartment', 'fromProject', 'toProject']);
        });
    }

    public function approveIpa(IpaTransfer $moving, int $approverId): IpaTransfer
    {
        if (! $moving->isSubmitted()) {
            throw new HttpException(403, 'Only submitted IPAs can be approved.');
        }

        $moving->update([
            'status' => 'APPROVED',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return $moving->fresh(['approvedBy', 'fromProject', 'toProject', 'lines.equipment', 'user']);
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

    public function transferPdf(IpaTransfer $moving)
    {
        $moving->load([
            'approvedBy',
            'fromProject',
            'toProject',
            'lines.equipment',
            'user',
            'fromDepartment',
            'toDepartment',
        ]);

        return Pdf::loadView('pdf.ipa-transfer', [
            'transfer' => $moving,
        ])->setPaper('a4', 'portrait');
    }

    private function ensureDraft(IpaTransfer $moving): void
    {
        if (! $moving->isDraft()) {
            throw new HttpException(403, 'Only draft IPAs can be modified.');
        }
    }
}
