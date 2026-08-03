<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportJob;
use Illuminate\Support\Str;

class DataImporterService
{
    /**
     * Dry-run validation importer with preview and error reporting before committing (CU-007).
     */
    public function validateAndImport(string $fileName, string $entityType, array $rows, bool $commit = false): ImportJob
    {
        $totalRows = count($rows);
        $validRows = 0;
        $invalidRows = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowErrors = [];

            if ($entityType === 'customers') {
                if (empty($row['name'])) {
                    $rowErrors[] = "Row #" . ($index + 1) . ": Customer 'name' is required.";
                }
                if (empty($row['code'])) {
                    $rowErrors[] = "Row #" . ($index + 1) . ": Customer 'code' is required.";
                } elseif (Customer::where('code', $row['code'])->exists()) {
                    $rowErrors[] = "Row #" . ($index + 1) . ": Customer code '{$row['code']}' already exists.";
                }
            }

            if (!empty($rowErrors)) {
                $invalidRows++;
                $errors = array_merge($errors, $rowErrors);
            } else {
                $validRows++;

                if ($commit && $entityType === 'customers') {
                    Customer::create([
                        'id' => (string) Str::uuid(),
                        'name' => $row['name'],
                        'code' => $row['code'],
                        'customer_type' => $row['customer_type'] ?? 'outlet',
                        'tax_number' => $row['tax_number'] ?? null,
                        'phone' => $row['phone'] ?? null,
                    ]);
                }
            }
        }

        $status = $commit ? ($invalidRows > 0 ? 'failed' : 'committed') : 'dry_run';

        return ImportJob::create([
            'file_name' => $fileName,
            'entity_type' => $entityType,
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'errors' => $errors,
            'status' => $status,
        ]);
    }
}
