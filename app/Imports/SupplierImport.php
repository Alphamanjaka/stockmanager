<?php

namespace App\Imports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SupplierImport implements ToModel, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $supplier = Supplier::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
            ]
        );

        if ($supplier->wasRecentlyCreated) {
            $this->created++;
        } elseif ($supplier->wasChanged()) {
            $this->updated++;
        }

        return $supplier;
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
