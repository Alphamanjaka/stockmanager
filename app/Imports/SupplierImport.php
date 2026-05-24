<?php

namespace App\Imports;

use App\Services\SupplierService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SupplierImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;

    public function __construct(private SupplierService $supplierService) {}

    /**
     * @param Row $row
     */
    public function onRow(Row $row)
    {
        $rowData = $row->toArray();
        $rowIndex = $row->getIndex();

        try {
            $email = mb_strtolower(trim($rowData['email']));
            $name = mb_strtolower(trim($rowData['name']));

            $data = [
                'name' => $name,
                'email' => $email,
                'phone' => $rowData['phone'] ?? null,
                'address' => $rowData['address'] ?? null,
            ];

            $existing = $this->supplierService->findByEmail($email);

            if ($existing) {
                $this->supplierService->updateSupplier($existing, $data);
                $this->updated++;
            } else {
                $this->supplierService->createSupplier($data);
                $this->created++;
            }
        } catch (\Throwable $e) {
            throw new \Exception("Ligne {$rowIndex} : " . $e->getMessage());
        }
    }
    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ];
    }

    public function getReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failures' => 0,
            'failure_details' => [],
        ];
    }
}
